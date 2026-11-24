@extends('layouts.auth')

@section('title', 'Masuk ke Akun')

@section('content')
<div class="auth-container">
    <!-- Left Panel: Branding & Value Proposition -->
    <div class="auth-brand-panel">
        <div class="brand-content">
            <div class="brand-badge">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5"/>
                    <path d="M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12"/>
                    <path d="m14 16-3 3 3 3"/>
                    <path d="M8.293 13.596 7.196 9.5 3.1 10.598"/>
                    <path d="m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843"/>
                    <path d="m13.378 9.633 4.096 1.098 1.097-4.096"/>
                </svg>
                <span>Bank Sampah Induk Digital</span>
            </div>

            <h1 class="brand-title">Si-Pilah</h1>
            <p class="brand-subtitle">
                Sistem Informasi Bank Sampah Induk dan Konversi Tabungan Komoditas Warga. Ubah limbah terpilah menjadi kebutuhan pokok secara transparan.
            </p>

            <ul class="feature-list">
                <li>
                    <span class="feature-icon">🚚</span>
                    <div>
                        <strong>Penjemputan Terjadwal</strong>
                        <p>Ajukan jadwal jemput sampah terpilah langsung dari rumah.</p>
                    </div>
                </li>
                <li>
                    <span class="feature-icon">📒</span>
                    <div>
                        <strong>Buku Besar (Ledger) Real-Time</strong>
                        <p>Pantau hasil penimbangan dan mutasi saldo tanpa buku fisik.</p>
                    </div>
                </li>
                <li>
                    <span class="feature-icon">🌾</span>
                    <div>
                        <strong>Konversi Komoditas Sembako</strong>
                        <p>Tukar saldo tabungan menjadi beras, minyak goreng, atau pupuk.</p>
                    </div>
                </li>
            </ul>
        </div>
        <div class="brand-footer">
            &copy; {{ date('Y') }} Si-Pilah — Tim Pengembang PWEB
        </div>
    </div>

    <!-- Right Panel: Login Form -->
    <div class="auth-form-panel">
        <div class="form-box">
            <div class="form-header">
                <h2>Selamat Datang Kembali</h2>
                <p>Silakan masuk menggunakan akun Warga, Petugas Lapangan, atau Admin Pengelola.</p>
            </div>

            <!-- Session Status / Error Alerts -->
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Gagal Masuk!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Standard Email & Password Form -->
            <form action="{{ route('login.post') }}" method="POST" id="loginForm" novalidate>
                @csrf

                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="nama@email.com" 
                        required 
                        autofocus
                        class="@error('email') is-invalid @enderror"
                    >
                    <span class="error-text" id="emailError"></span>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password">Kata Sandi</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
                        @endif
                    </div>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan kata sandi" 
                            required
                            class="@error('password') is-invalid @enderror"
                        >
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan kata sandi">
                            <!-- Eye Icon -->
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <span class="error-text" id="passwordError"></span>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        Ingat sesi saya
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="btn-text">Masuk ke Sistem</span>
                    <span class="btn-loader" hidden>Memproses...</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>atau khusus Warga (Nasabah)</span>
            </div>

            <!-- Google OAuth 2.0 Button (Per Section II.6 & III.4 of Proposal) -->
            <a href="{{ route('auth.google') }}" class="btn btn-google" id="googleLoginBtn">
                <svg width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>
            <p class="oauth-note">
                *Login Google otomatis mendaftarkan/masuk sebagai peran <strong>Warga (Nasabah)</strong>.
            </p>

            <!-- Register Link for Warga -->
            @if (Route::has('register'))
                <div class="form-footer">
                    Belum punya akun nasabah? <a href="{{ route('register') }}">Daftar Warga Baru</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection