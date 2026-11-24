<?php

use App\Models\Commodity;
use App\Models\CommodityVoucher;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteSale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: User, 2: WasteCategory, 3: Commodity}
 */
function adminFixtures(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $warga = User::factory()->create(['role' => 'warga']);

    $category = WasteCategory::create([
        'name' => 'Botol Plastik PET Bersih',
        'unit' => 'kg',
        'buy_price' => 3500,
        'sell_price' => 5000,
        'description' => 'Tanpa label',
    ]);
    $category->stock = 10;
    $category->save();

    $commodity = Commodity::create([
        'name' => 'Beras Medium 5 Kg',
        'price' => 65000,
        'stock' => 5,
        'unit' => 'karung',
    ]);

    return [$admin, $warga, $category, $commodity];
}

function voucherFor(User $warga, Commodity $commodity, int $quantity = 2): CommodityVoucher
{
    return CommodityVoucher::create([
        'voucher_code' => 'VCH-TEST'.$commodity->id.$quantity,
        'user_id' => $warga->id,
        'commodity_id' => $commodity->id,
        'quantity' => $quantity,
        'total_cost' => $commodity->price * $quantity,
        'status' => 'aktif',
    ]);
}

test('admin dashboard renders all management modules', function () {
    [$admin] = adminFixtures();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Kategori Sampah')
        ->assertSee('Katalog Komoditas')
        ->assertSee('Rekapitulasi Bulanan');
});

test('non admin roles cannot open the admin dashboard', function () {
    [, $warga] = adminFixtures();

    $this->actingAs($warga)->get(route('admin.dashboard'))->assertForbidden();
});

test('admin can create and update a waste category', function () {
    [$admin] = adminFixtures();

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Kaleng Aluminium',
        'unit' => 'kg',
        'buy_price' => 11000,
        'sell_price' => 14500,
        'description' => 'Kaleng minuman bersih',
    ])->assertSessionHasNoErrors();

    $category = WasteCategory::where('name', 'Kaleng Aluminium')->firstOrFail();

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => 'Kaleng Aluminium',
        'unit' => 'kg',
        'buy_price' => 12000,
        'sell_price' => 15000,
        'description' => 'Kaleng minuman bersih',
    ])->assertSessionHasNoErrors();

    expect((float) $category->fresh()->buy_price)->toBe(12000.0);
});

test('validating a voucher marks it used and reduces commodity stock once', function () {
    [$admin, $warga, , $commodity] = adminFixtures();
    $voucher = voucherFor($warga, $commodity, 2);

    $this->actingAs($admin)
        ->post(route('admin.vouchers.validate', $voucher))
        ->assertSessionHasNoErrors();

    expect($voucher->fresh()->status)->toBe('digunakan')
        ->and($commodity->fresh()->stock)->toBe(3);

    $this->actingAs($admin)
        ->post(route('admin.vouchers.validate', $voucher))
        ->assertSessionHasErrors('voucher');

    expect($commodity->fresh()->stock)->toBe(3);
});

test('voucher validation is refused when warehouse stock is not enough', function () {
    [$admin, $warga, , $commodity] = adminFixtures();
    $commodity->update(['stock' => 1]);
    $voucher = voucherFor($warga, $commodity, 3);

    $this->actingAs($admin)
        ->post(route('admin.vouchers.validate', $voucher))
        ->assertSessionHasErrors('voucher');

    expect($voucher->fresh()->status)->toBe('aktif')
        ->and($commodity->fresh()->stock)->toBe(1);
});

test('recording a waste sale computes total and reduces collected stock', function () {
    [$admin, , $category] = adminFixtures();

    $this->actingAs($admin)->post(route('admin.sales.store'), [
        'waste_category_id' => $category->id,
        'weight' => 4,
        'price_per_unit' => 5000,
        'buyer' => 'PT Daur Plastik',
        'sold_at' => today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $sale = WasteSale::firstOrFail();

    expect((float) $sale->total)->toBe(20000.0)
        ->and((float) $category->fresh()->stock)->toBe(6.0);
});

test('waste sale cannot exceed collected stock', function () {
    [$admin, , $category] = adminFixtures();

    $this->actingAs($admin)->post(route('admin.sales.store'), [
        'waste_category_id' => $category->id,
        'weight' => 50,
        'price_per_unit' => 5000,
        'sold_at' => today()->toDateString(),
    ])->assertSessionHasErrors('weight');

    expect(WasteSale::count())->toBe(0)
        ->and((float) $category->fresh()->stock)->toBe(10.0);
});

test('a category used by transactions cannot be deleted', function () {
    [$admin, $warga, $category] = adminFixtures();

    PickupRequest::create([
        'user_id' => $warga->id,
        'pickup_date' => today()->toDateString(),
        'time_slot' => 'Pagi (08:00 - 11:00)',
        'pickup_address' => 'Jl. Melati No. 14',
        'estimated_notes' => 'Botol plastik',
        'status' => 'menunggu',
    ])->items()->create([
        'waste_category_id' => $category->id,
        'weight' => 1,
        'unit_price' => 3500,
        'subtotal' => 3500,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('category');

    expect(WasteCategory::find($category->id))->not->toBeNull();
});
