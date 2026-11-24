<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Warga - Si-Pilah Bank Sampah Induk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/warga-dashboard.css') }}">
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="topbar">
        <div class="container topbar-inner">
            <div class="brand">
                <span class="brand-logo">♻️</span>
                <div>
                    <span class="brand-name">Si-Pilah</span>
                    <span class="brand-role">Portal Warga (Nasabah)</span>
                </div>
            </div>

            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name">{{ $user->name }}</span>
                    <span class="user-email">{{ $user->email }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container main-content">
        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Terjadi Kesalahan:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Ringkasan Saldo & Statistik (Hero Cards) -->
        <section class="stats-grid">
            <div class="stat-card stat-primary">
                <span class="stat-label">Saldo Tabungan Komoditas</span>
                <h2 class="stat-value">Rp {{ number_format($user->balance, 0, ',', '.') }}</h2>
                <p class="stat-hint">Dapat langsung ditukar dengan beras, minyak, atau pupuk.</p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Total Sampah Terpilah</span>
                <h2 class="stat-value">{{ number_format($totalWeightSetor, 1, ',', '.') }} Kg</h2>
                <p class="stat-hint">Akumulasi setoran tervalidasi petugas.</p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Voucher Komoditas Aktif</span>
                <h2 class="stat-value">{{ $vouchers->where('status', 'aktif')->count() }} Voucher</h2>
                <p class="stat-hint">Siap ditukarkan di pos Bank Sampah Induk.</p>
            </div>
        </section>

        <!-- Tab Navigasi Modul Warga -->
        <nav class="tab-nav" aria-label="Menu Dashboard Warga">
            <button type="button" class="tab-btn active" data-target="tab-jemput">🚚 Ajukan Penjemputan</button>
            <button type="button" class="tab-btn" data-target="tab-komoditas">🌾 Tukar Komoditas</button>
            <button type="button" class="tab-btn" data-target="tab-ledger">📒 Buku Besar (Ledger)</button>
            <button type="button" class="tab-btn" data-target="tab-harga">🏷️ Daftar Harga Sampah</button>
        </nav>

        <!-- TAB 1: AJUKAN PENJEMPUTAN & STATUS ANTREAN -->
        <section id="tab-jemput" class="tab-panel active">
            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Form Jadwal Jemput Sampah</h3>
                        <p>Pastikan sampah sudah dipilah sesuai kategori sebelum dijemput petugas.</p>
                    </div>
                    <form action="{{ route('warga.pickup.store') }}" method="POST" class="form-stack">
                        @csrf
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pickup_date">Tanggal Penjemputan</label>
                                <input type="date" id="pickup_date" name="pickup_date" min="{{ date('Y-m-d') }}" value="{{ old('pickup_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="form-group">
                                <label for="time_slot">Sesi Waktu</label>
                                <select id="time_slot" name="time_slot" required>
                                    <option value="Pagi (08:00 - 11:00)">Pagi (08:00 - 11:00)</option>
                                    <option value="Sore (14:00 - 17:00)">Sore (14:00 - 17:00)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pickup_address">Alamat Lengkap Penjemputan</label>
                            <textarea id="pickup_address" name="pickup_address" rows="2" placeholder="Contoh: Jl. Melati No. 14, RT 03/RW 05" required>{{ old('pickup_address', $user->address) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="estimated_notes">Rincian Sampah Terpilah</label>
                            <textarea id="estimated_notes" name="estimated_notes" rows="2" placeholder="Contoh: 2 karung botol plastik PET, 5 kg kardus kering" required>{{ old('estimated_notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Kirim Permintaan Jemput</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Riwayat & Status Penjemputan</h3>
                        <p>Pantau progres penjemputan hingga penimbangan selesai.</p>
                    </div>
                    @if ($pickups->isEmpty())
                        <div class="empty-state">Belum ada pengajuan penjemputan sampah.</div>
                    @else
                        <div class="pickup-list">
                            @foreach ($pickups as $pickup)
                                <div class="pickup-item">
                                    <div class="pickup-top">
                                        <strong>{{ \Carbon\Carbon::parse($pickup->pickup_date)->translatedFormat('d M Y') }} • {{ $pickup->time_slot }}</strong>
                                        <span class="badge badge-{{ $pickup->status }}">{{ ucfirst($pickup->status) }}</span>
                                    </div>
                                    <p class="pickup-notes">{{ $pickup->estimated_notes }}</p>
                                    @if ($pickup->status === 'selesai')
                                        <div class="pickup-result">
                                            Bobot Valid: <strong>{{ $pickup->total_weight }} Kg</strong> | Saldo Masuk: <strong>+Rp {{ number_format($pickup->total_earned, 0, ',', '.') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- TAB 2: TUKAR SALDO KE KOMODITAS SEMBAKO & VOUCHER -->
        <section id="tab-komoditas" class="tab-panel">
            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Katalog Komoditas Sembako & Pupuk</h3>
                        <p>Tukar saldo tabungan menjadi voucher pengambilan kebutuhan pokok.</p>
                    </div>
                    <div class="commodity-grid">
                        @foreach ($commodities as $item)
                            <div class="commodity-card">
                                <div>
                                    <h4>{{ $item->name }}</h4>
                                    <span class="commodity-price">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                                    <span class="commodity-stock">Stok Gudang: {{ $item->stock }} {{ $item->unit }}</span>
                                </div>
                                <form action="{{ route('warga.commodity.redeem') }}" method="POST" class="redeem-form" data-name="{{ $item->name }}" data-price="{{ $item->price }}">
                                    @csrf
                                    <input type="hidden" name="commodity_id" value="{{ $item->id }}">
                                    <div class="redeem-controls">
                                        <input type="number" name="quantity" value="1" min="1" max="{{ min(5, $item->stock) }}" aria-label="Jumlah">
                                        <button type="submit" class="btn btn-primary btn-sm" {{ $user->balance < $item->price ? 'disabled' : '' }}>
                                            {{ $user->balance < $item->price ? 'Saldo Kurang' : 'Tukar' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Voucher Penukaran Saya</h3>
                        <p>Tunjukkan kode voucher aktif kepada Admin/Petugas saat pengambilan barang.</p>
                    </div>
                    @if ($vouchers->isEmpty())
                        <div class="empty-state">Anda belum memiliki voucher komoditas.</div>
                    @else
                        <div class="voucher-list">
                            @foreach ($vouchers as $vch)
                                <div class="voucher-item">
                                    <div>
                                        <span class="voucher-code">{{ $vch->voucher_code }}</span>
                                        <p class="voucher-detail">{{ $vch->quantity }}x {{ $vch->commodity->name }} (Rp {{ number_format($vch->total_cost, 0, ',', '.') }})</p>
                                    </div>
                                    <span class="badge badge-{{ $vch->status }}">{{ ucfirst($vch->status) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- TAB 3: BUKU BESAR DIGITAL (LEDGER) -->
        <section id="tab-ledger" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>Mutasi Buku Besar Tabungan (Ledger)</h3>
                    <p>Catatan transparan seluruh pemasukan setoran sampah (Kredit) dan penukaran sembako (Debit).</p>
                </div>
                @if ($mutations->isEmpty())
                    <div class="empty-state">Belum ada riwayat transaksi pada buku besar Anda.</div>
                @else
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan Transaksi</th>
                                    <th>Jenis</th>
                                    <th>Nominal</th>
                                    <th>Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mutations as $mut)
                                    <tr>
                                        <td>{{ $mut->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $mut->description }}</td>
                                        <td>
                                            <span class="badge badge-{{ $mut->type }}">
                                                {{ $mut->type === 'kredit' ? 'Setoran (+)' : 'Penukaran (-)' }}
                                            </span>
                                        </td>
                                        <td class="{{ $mut->type === 'kredit' ? 'text-credit' : 'text-debit' }}">
                                            {{ $mut->type === 'kredit' ? '+' : '-' }}Rp {{ number_format($mut->amount, 0, ',', '.') }}
                                        </td>
                                        <td><strong>Rp {{ number_format($mut->balance_after, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <!-- TAB 4: DAFTAR HARGA SAMPAH TERPILAH -->
        <section id="tab-harga" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Harga Beli Sampah Terpilah</h3>
                    <p>Acuan harga konversi saldo per satuan berat/volume saat penimbangan petugas.</p>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kategori Sampah</th>
                                <th>Syarat Pemilahan</th>
                                <th>Harga Beli Warga</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($wasteCategories as $cat)
                                <tr>
                                    <td><strong>{{ $cat->name }}</strong></td>
                                    <td>{{ $cat->description ?? '-' }}</td>
                                    <td class="text-credit"><strong>Rp {{ number_format($cat->buy_price, 0, ',', '.') }} / {{ $cat->unit }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/warga-dashboard.js') }}" defer></script>
</body>
</html>