<?php

use App\Models\Commodity;
use App\Models\CommodityVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('warga dashboard ships a custom redemption confirmation modal', function () {
    $warga = User::factory()->create(['role' => 'warga', 'balance' => 0]);

    $this->actingAs($warga)
        ->get(route('warga.dashboard'))
        ->assertOk()
        ->assertSee('id="redeemModal"', false)
        ->assertSee('data-modal-confirm', false);
});

test('warga dashboard script no longer calls the browser confirm dialog', function () {
    $script = file_get_contents(public_path('js/warga-dashboard.js'));

    expect($script)->toContain('redeemModal')
        ->and($script)->not->toContain('confirm(');
});

test('redeeming a commodity keeps warehouse stock until admin validates the voucher', function () {
    $warga = User::factory()->create(['role' => 'warga', 'balance' => 200000]);

    $commodity = Commodity::create([
        'name' => 'Beras Medium 5 Kg',
        'price' => 65000,
        'stock' => 5,
        'unit' => 'karung',
    ]);

    $this->actingAs($warga)
        ->post(route('warga.commodity.redeem'), [
            'commodity_id' => $commodity->id,
            'quantity' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($commodity->fresh()->stock)->toBe(5)
        ->and(CommodityVoucher::where('status', 'aktif')->count())->toBe(1)
        ->and((float) $warga->fresh()->balance)->toBe(70000.0);
});
