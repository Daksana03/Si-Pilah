<?php

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
