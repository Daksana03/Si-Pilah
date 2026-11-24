<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Admin - Si-Pilah Bank Sampah Induk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/warga-dashboard.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <div class="brand">
                <span class="brand-logo">♻️</span>
                <div>
                    <span class="brand-name">Si-Pilah</span>
                    <span class="brand-role">Portal Admin Pengelola</span>
                </div>
            </div>

            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-email">{{ auth()->user()->email }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container main-content">
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

        <section class="stats-grid">
            <div class="stat-card stat-primary">
                <span class="stat-label">Stok Limbah Terkumpul</span>
                <h2 class="stat-value">{{ number_format($totalWasteStock, 2, ',', '.') }} Unit</h2>
                <p class="stat-hint">Akumulasi hasil penimbangan petugas yang belum dijual ke pabrik.</p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Stok Komoditas Gudang</span>
                <h2 class="stat-value">{{ number_format($totalCommodityStock, 0, ',', '.') }} Paket</h2>
                <p class="stat-hint">Voucher aktif menunggu validasi: <strong>{{ $voucherPending }}</strong></p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Penjualan Limbah Bulan Ini</span>
                <h2 class="stat-value">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</h2>
                <p class="stat-hint">Kas masuk dari penjualan ke pabrik daur ulang.</p>
            </div>
        </section>

        <nav class="tab-nav" aria-label="Menu Dashboard Admin">
            <button type="button" class="tab-btn active" data-target="tab-kategori">🏷️ Kategori Sampah</button>
            <button type="button" class="tab-btn" data-target="tab-komoditas">🌾 Katalog Komoditas</button>
            <button type="button" class="tab-btn" data-target="tab-voucher">🎫 Validasi Voucher ({{ $voucherPending }})</button>
            <button type="button" class="tab-btn" data-target="tab-penjualan">🏭 Penjualan Limbah</button>
        </nav>

        <!-- TAB 1: DATA MASTER KATEGORI SAMPAH -->
        <section id="tab-kategori" class="tab-panel active">
            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Kategori Sampah</h3>
                        <p>Nama jenis, satuan, harga beli dari warga, dan harga jual ke pabrik.</p>
                    </div>
                    <form action="{{ route('admin.categories.store') }}" method="POST" class="form-stack">
                        @csrf
                        <div class="form-group">
                            <label for="cat-name">Nama Kategori</label>
                            <input type="text" id="cat-name" name="name" value="{{ old('name') }}" placeholder="Contoh: Botol Plastik PET Bersih" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cat-unit">Satuan</label>
                                <input type="text" id="cat-unit" name="unit" value="{{ old('unit', 'kg') }}" placeholder="kg / liter" required>
                            </div>
                            <div class="form-group">
                                <label for="cat-buy">Harga Beli Warga</label>
                                <input type="number" id="cat-buy" name="buy_price" value="{{ old('buy_price') }}" min="0" step="100" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cat-sell">Harga Jual ke Pabrik</label>
                            <input type="number" id="cat-sell" name="sell_price" value="{{ old('sell_price') }}" min="0" step="100" required>
                        </div>
                        <div class="form-group">
                            <label for="cat-desc">Syarat Pemilahan</label>
                            <textarea id="cat-desc" name="description" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Kategori &amp; Stok Terkumpul</h3>
                        <p>Stok bertambah saat penimbangan petugas, berkurang saat penjualan ke pabrik.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Beli / Jual</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($wasteCategories as $cat)
                                    <tr>
                                        <td>
                                            <strong>{{ $cat->name }}</strong><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">{{ $cat->description ?? '-' }}</span>
                                        </td>
                                        <td>Rp {{ number_format($cat->buy_price, 0, ',', '.') }} / {{ number_format($cat->sell_price, 0, ',', '.') }}</td>
                                        <td>{{ number_format((float) $cat->stock, 2, ',', '.') }} {{ $cat->unit }}</td>
                                        <td>
                                            <details>
                                                <summary style="cursor: pointer; color: #1e40af; font-weight: 600; font-size: 0.82rem;">Ubah</summary>
                                                <form action="{{ route('admin.categories.update', $cat) }}" method="POST" class="form-stack" style="margin-top: 0.6rem; min-width: 170px;">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" value="{{ $cat->name }}" required>
                                                    <input type="text" name="unit" value="{{ $cat->unit }}" required>
                                                    <input type="number" name="buy_price" value="{{ (float) $cat->buy_price }}" min="0" step="100" required>
                                                    <input type="number" name="sell_price" value="{{ (float) $cat->sell_price }}" min="0" step="100" required>
                                                    <input type="text" name="description" value="{{ $cat->description }}" placeholder="Syarat pemilahan">
                                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                                </form>
                                                <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" style="margin-top: 0.4rem;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-ghost">Hapus</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- TAB 2: KATALOG KOMODITAS -->
        <section id="tab-komoditas" class="tab-panel">
            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Tambah Komoditas</h3>
                        <p>Nama komoditas, harga tebus saldo, dan jumlah stok gudang.</p>
                    </div>
                    <form action="{{ route('admin.commodities.store') }}" method="POST" class="form-stack">
                        @csrf
                        <div class="form-group">
                            <label for="com-name">Nama Komoditas</label>
                            <input type="text" id="com-name" name="name" value="{{ old('name') }}" placeholder="Contoh: Beras Medium 5 Kg" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="com-price">Harga Tebus (Rp)</label>
                                <input type="number" id="com-price" name="price" value="{{ old('price') }}" min="0" step="500" required>
                            </div>
                            <div class="form-group">
                                <label for="com-stock">Stok Gudang</label>
                                <input type="number" id="com-stock" name="stock" value="{{ old('stock', 0) }}" min="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="com-unit">Satuan</label>
                            <input type="text" id="com-unit" name="unit" value="{{ old('unit', 'paket') }}" placeholder="karung / pouch / bungkus" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Komoditas</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Katalog &amp; Stok Komoditas</h3>
                        <p>Stok berkurang saat voucher warga divalidasi.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Komoditas</th>
                                    <th>Harga Tebus</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($commodities as $item)
                                    <tr>
                                        <td><strong>{{ $item->name }}</strong></td>
                                        <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td>{{ $item->stock }} {{ $item->unit }}</td>
                                        <td>
                                            <details>
                                                <summary style="cursor: pointer; color: #1e40af; font-weight: 600; font-size: 0.82rem;">Ubah</summary>
                                                <form action="{{ route('admin.commodities.update', $item) }}" method="POST" class="form-stack" style="margin-top: 0.6rem; min-width: 170px;">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" value="{{ $item->name }}" required>
                                                    <input type="number" name="price" value="{{ (float) $item->price }}" min="0" step="500" required>
                                                    <input type="number" name="stock" value="{{ $item->stock }}" min="0" required>
                                                    <input type="text" name="unit" value="{{ $item->unit }}" required>
                                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                                </form>
                                                <form action="{{ route('admin.commodities.destroy', $item) }}" method="POST" style="margin-top: 0.4rem;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-ghost">Hapus</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>


        <!-- TAB 3: VALIDASI VOUCHER KOMODITAS -->
        <section id="tab-voucher" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h3>Voucher Menunggu Validasi</h3>
                    <p>Cocokkan kode voucher yang dibawa warga, lalu validasi. Stok komoditas otomatis berkurang.</p>
                </div>

                @if ($vouchers->isEmpty())
                    <div class="empty-state">Tidak ada voucher aktif yang menunggu validasi.</div>
                @else
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode Voucher</th>
                                    <th>Warga</th>
                                    <th>Komoditas</th>
                                    <th>Nilai</th>
                                    <th>Diterbitkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vouchers as $voucher)
                                    <tr>
                                        <td><span class="voucher-code">{{ $voucher->voucher_code }}</span></td>
                                        <td>
                                            <strong>{{ $voucher->user->name }}</strong><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">{{ $voucher->user->email }}</span>
                                        </td>
                                        <td>{{ $voucher->quantity }}x {{ $voucher->commodity->name }}</td>
                                        <td>Rp {{ number_format($voucher->total_cost, 0, ',', '.') }}</td>
                                        <td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form action="{{ route('admin.vouchers.validate', $voucher) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Validasi</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>


        <!-- TAB 4: PENJUALAN LIMBAH KE PABRIK + REKAP BULANAN -->
        <section id="tab-penjualan" class="tab-panel">
            <div class="two-col-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Catat Penjualan Limbah</h3>
                        <p>Total kas dihitung otomatis dari berat × harga jual. Stok terkumpul berkurang sesuai berat.</p>
                    </div>
                    <form action="{{ route('admin.sales.store') }}" method="POST" class="form-stack">
                        @csrf
                        <div class="form-group">
                            <label for="sale-category">Kategori Limbah</label>
                            <select id="sale-category" name="waste_category_id" required>
                                <option value="">— Pilih kategori —</option>
                                @foreach ($wasteCategories as $cat)
                                    <option value="{{ $cat->id }}" data-sell-price="{{ (float) $cat->sell_price }}">
                                        {{ $cat->name }} (stok {{ number_format((float) $cat->stock, 2, ',', '.') }} {{ $cat->unit }} • jual Rp {{ number_format($cat->sell_price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sale-weight">Berat Dijual</label>
                                <input type="number" id="sale-weight" name="weight" min="0.01" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="sale-price">Harga Jual per Satuan</label>
                                <input type="number" id="sale-price" name="price_per_unit" min="0" step="100" value="{{ $wasteCategories->first()->sell_price ?? '' }}" required>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Otomatis dari kategori terpilih; ubah hanya bila pabrik menawar beda.</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sale-date">Tanggal Jual</label>
                                <input type="date" id="sale-date" name="sold_at" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="sale-buyer">Pabrik / Pengepul</label>
                                <input type="text" id="sale-buyer" name="buyer" placeholder="Contoh: PT Daur Plastik">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Penjualan</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Rekapitulasi Bulanan</h3>
                        <p>Total berat terjual dan kas masuk per bulan (maks. 6 bulan terakhir).</p>
                    </div>
                    @if ($monthlyRecap->isEmpty())
                        <div class="empty-state">Belum ada penjualan tercatat.</div>
                    @else
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Berat Terjual</th>
                                        <th>Kas Masuk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($monthlyRecap as $recap)
                                        <tr>
                                            <td><strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $recap['month'])->translatedFormat('F Y') }}</strong></td>
                                            <td>{{ number_format($recap['weight'], 2, ',', '.') }}</td>
                                            <td class="text-credit">Rp {{ number_format($recap['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>


            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3>Riwayat Penjualan Terakhir</h3>
                    <p>15 transaksi terbaru ke mitra pabrik daur ulang.</p>
                </div>
                @if ($sales->isEmpty())
                    <div class="empty-state">Belum ada penjualan limbah.</div>
                @else
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Berat</th>
                                    <th>Harga / Satuan</th>
                                    <th>Total</th>
                                    <th>Pembeli</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sales as $sale)
                                    <tr>
                                        <td>{{ $sale->sold_at->format('d/m/Y') }}</td>
                                        <td>{{ $sale->category->name }}</td>
                                        <td>{{ number_format((float) $sale->weight, 2, ',', '.') }} {{ $sale->category->unit }}</td>
                                        <td>Rp {{ number_format($sale->price_per_unit, 0, ',', '.') }}</td>
                                        <td class="text-credit">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                        <td>{{ $sale->buyer ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    </main>

    <script src="{{ asset('js/warga-dashboard.js') }}" defer></script>

    <script>
        // Harga jual mengikuti kategori terpilih, tetap bisa diubah per transaksi
        const saleCategory = document.getElementById('sale-category');
        const salePrice = document.getElementById('sale-price');

        if (saleCategory && salePrice) {
            saleCategory.addEventListener('change', () => {
                const option = saleCategory.selectedOptions[0];

                if (option && option.dataset.sellPrice) {
                    salePrice.value = Number(option.dataset.sellPrice);
                }
            });
        }
    </script>
</body>
</html>

