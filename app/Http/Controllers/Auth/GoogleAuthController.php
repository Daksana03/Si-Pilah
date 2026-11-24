<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $driver = Socialite::driver('google');

            // Bypass local Windows SSL cert issue during development
            if (app()->isLocal()) {
                $driver->setHttpClient(new Client(['verify' => false]));
            }

            $googleUser = $driver->user();

            $user = User::where('google_id', $googleUser->getId())
                        ->orWhere('email', $googleUser->getEmail())
                        ->first();

            if ($user) {
                // Batasan Sistem #7: Google OAuth is exclusively for Warga (Nasabah)
                if ($user->role !== 'warga') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Login Google hanya diperuntukkan bagi akun Warga (Nasabah).',
                    ]);
                }

                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            } else {
                // Auto-create new Warga account
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'role' => 'warga',
                    'email_verified_at' => now(),
                ]);
            }

            Auth::login($user, true);

            return redirect()->intended('/warga/dashboard');

        } catch (Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Autentikasi Google gagal: ' . $e->getMessage(),
            ]);
        }
    }
}