<?php

use App\Models\LedgerMutation;
use App\Models\PickupItem;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: User, 2: WasteCategory, 3: WasteCategory, 4: PickupRequest}
 */
function petugasFixtures(): array
{
    $petugas = User::factory()->create(['role' => 'petugas']);
    $warga = User::factory()->create(['role' => 'warga', 'balance' => 0]);

    $pet = WasteCategory::create([
        'name' => 'Botol Plastik PET Bersih',
        'unit' => 'kg',
        'buy_price' => 3500,
        'sell_price' => 5000,
        'description' => 'Botol air mineral tanpa label',
    ]);

    $kardus = WasteCategory::create([
        'name' => 'Kardus & Kertas Karton',
        'unit' => 'kg',
        'buy_price' => 2200,
        'sell_price' => 3200,
        'description' => 'Kardus kering terlipat rapi',
    ]);

    $pickup = PickupRequest::create([
        'user_id' => $warga->id,
        'pickup_date' => today()->toDateString(),
        'time_slot' => 'Pagi (08:00 - 11:00)',
        'pickup_address' => 'Jl. Melati No. 14, RT 03/RW 05',
        'estimated_notes' => '2 karung botol plastik PET',
        'status' => 'menunggu',
    ]);

    return [$petugas, $warga, $pet, $kardus, $pickup];
}

test('petugas dashboard shows the start button before the weighing form', function () {
    [$petugas] = petugasFixtures();

    $this->actingAs($petugas)
        ->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertSee('Antrean Penjemputan Sampah')
        ->assertSee('Mulai Jemput')
        ->assertDontSee('Simpan Timbangan');
});

test('petugas can start a pickup', function () {
    [$petugas, , , , $pickup] = petugasFixtures();

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.start', $pickup))
        ->assertSessionHasNoErrors();

    expect($pickup->fresh()->status)->toBe('dijemput')
        ->and($pickup->fresh()->petugas_id)->toBe($petugas->id);
});

test('weighing is rejected until the pickup is started', function () {
    [$petugas, , $pet, , $pickup] = petugasFixtures();

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 2]])
        ->assertSessionHasErrors('pickup');

    expect($pickup->fresh()->status)->toBe('menunggu');
});

test('weighing per category credits balance, locks price and adds warehouse stock', function () {
    [$petugas, $warga, $pet, $kardus, $pickup] = petugasFixtures();

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.start', $pickup))
        ->assertSessionHasNoErrors();

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.weigh', $pickup), [
            'weights' => [$pet->id => 2.5, $kardus->id => 1],
        ])
        ->assertSessionHasNoErrors();

    $pickup->refresh();

    expect($pickup->status)->toBe('ditimbang')
        ->and((float) $pickup->total_weight)->toBe(3.5)
        ->and((float) $pickup->total_earned)->toBe(10950.0)
        ->and((float) $warga->fresh()->balance)->toBe(10950.0)
        ->and(PickupItem::where('pickup_request_id', $pickup->id)->count())->toBe(2)
        ->and((float) $pet->fresh()->stock)->toBe(2.5)
        ->and((float) $kardus->fresh()->stock)->toBe(1.0)
        ->and((float) LedgerMutation::where('user_id', $warga->id)->sum('amount'))->toBe(10950.0);
});

test('weighing twice is rejected so balance cannot be credited twice', function () {
    [$petugas, , $pet, , $pickup] = petugasFixtures();

    $payload = ['weights' => [$pet->id => 1]];

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));
    $this->actingAs($petugas)->post(route('petugas.pickup.weigh', $pickup), $payload);
    $this->actingAs($petugas)
        ->post(route('petugas.pickup.weigh', $pickup), $payload)
        ->assertSessionHasErrors('pickup');
});

test('petugas finishes a weighed pickup', function () {
    [$petugas, , $pet, , $pickup] = petugasFixtures();

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));
    $this->actingAs($petugas)->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 1]]);
    $this->actingAs($petugas)

        ->post(route('petugas.pickup.finish', $pickup))
        ->assertSessionHasNoErrors();

    expect($pickup->fresh()->status)->toBe('selesai');
});

test('a pickup cannot be finished before weighing', function () {
    [$petugas, , , , $pickup] = petugasFixtures();

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.finish', $pickup))
        ->assertSessionHasErrors('pickup');
});

test('another petugas cannot take over a started pickup', function () {
    [$petugas, , $pet, , $pickup] = petugasFixtures();
    $lain = User::factory()->create(['role' => 'petugas']);

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));

    $this->actingAs($lain)
        ->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 1]])
        ->assertSessionHasErrors('pickup');

    expect($pickup->fresh()->status)->toBe('dijemput');
});

test('warga cannot weigh a pickup', function () {
    [, $warga, $pet, , $pickup] = petugasFixtures();

    $this->actingAs($warga)
        ->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 1]])
        ->assertForbidden();
});

test('petugas can correct a saved weighing and totals are recalculated', function () {
    [$petugas, $warga, $pet, $kardus, $pickup] = petugasFixtures();

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));
    $this->actingAs($petugas)->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 2]]);

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.reweigh', $pickup), ['weights' => [$kardus->id => 3]])
        ->assertSessionHasNoErrors();

    expect((float) $pickup->fresh()->total_weight)->toBe(3.0)
        ->and((float) $pickup->fresh()->total_earned)->toBe(6600.0)
        ->and((float) $warga->fresh()->balance)->toBe(6600.0)
        ->and((float) $pet->fresh()->stock)->toBe(0.0)
        ->and((float) $kardus->fresh()->stock)->toBe(3.0)
        ->and(PickupItem::where('pickup_request_id', $pickup->id)->count())->toBe(1)
        ->and(LedgerMutation::where('user_id', $warga->id)->where('type', 'debit')->count())->toBe(1);
});

test('cancelling a weighing refunds balance and stock and returns to dijemput', function () {
    [$petugas, $warga, $pet, , $pickup] = petugasFixtures();

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));
    $this->actingAs($petugas)->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 2]]);

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.cancel-weigh', $pickup))
        ->assertSessionHasNoErrors();

    $pickup->refresh();

    expect($pickup->status)->toBe('dijemput')
        ->and($pickup->total_weight)->toBeNull()
        ->and($pickup->total_earned)->toBeNull()
        ->and((float) $warga->fresh()->balance)->toBe(0.0)
        ->and((float) $pet->fresh()->stock)->toBe(0.0)
        ->and(PickupItem::where('pickup_request_id', $pickup->id)->count())->toBe(0);
});

test('correction is blocked when the warga already spent the credited balance', function () {
    [$petugas, $warga, $pet, , $pickup] = petugasFixtures();

    $this->actingAs($petugas)->post(route('petugas.pickup.start', $pickup));
    $this->actingAs($petugas)->post(route('petugas.pickup.weigh', $pickup), ['weights' => [$pet->id => 2]]);
    $warga->update(['balance' => 1000]);

    $this->actingAs($petugas)
        ->post(route('petugas.pickup.reweigh', $pickup), ['weights' => [$pet->id => 1]])
        ->assertSessionHasErrors('pickup');

    expect($pickup->fresh()->status)->toBe('ditimbang')
        ->and((float) $pickup->fresh()->total_weight)->toBe(2.0);
});
