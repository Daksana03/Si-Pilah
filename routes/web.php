<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Petugas\DashboardController as PetugasDashboardController;
use App\Http\Controllers\Warga\DashboardController as WargaDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. Smart Root Redirect (Prevents infinite loop when already logged in)
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'petugas' => redirect()->route('petugas.dashboard'),
            default => redirect()->route('warga.dashboard'),
        };
    }

    return redirect()->route('login');
});

// Fallback for Laravel's default 'guest' middleware redirect
Route::get('/home', fn () => redirect('/'));

// 2. Guest Routes (Only accessible when logged out)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    // Google OAuth 2.0 (Exclusively for Warga / Nasabah)
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// 3. Logout Route
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 4. Role-Protected Dashboards (Using CheckRole Middleware)
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/dashboard', [PetugasDashboardController::class, 'index'])->name('dashboard');
    Route::post('/pickup/{pickup}/start', [PetugasDashboardController::class, 'start'])->name('pickup.start');
    Route::post('/pickup/{pickup}/weigh', [PetugasDashboardController::class, 'weigh'])->name('pickup.weigh');
    Route::post('/pickup/{pickup}/reweigh', [PetugasDashboardController::class, 'reweigh'])->name('pickup.reweigh');
    Route::post('/pickup/{pickup}/cancel-weigh', [PetugasDashboardController::class, 'cancelWeigh'])->name('pickup.cancel-weigh');
    Route::post('/pickup/{pickup}/finish', [PetugasDashboardController::class, 'finish'])->name('pickup.finish');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return 'Halo '.Auth::user()->name.'! Ini Dashboard Admin Pengelola.
                <form method="POST" action="'.route('logout').'">'.csrf_field().'<button type="submit">Logout</button></form>';
    })->name('dashboard');
});

Route::middleware(['auth', 'role:warga'])->prefix('warga')->name('warga.')->group(function () {
    Route::get('/dashboard', [WargaDashboardController::class, 'index'])->name('dashboard');
    Route::post('/pickup', [WargaDashboardController::class, 'storePickup'])->name('pickup.store');
    Route::post('/redeem', [WargaDashboardController::class, 'redeemCommodity'])->name('commodity.redeem');
});
