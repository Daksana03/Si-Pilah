<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Petugas - Si-Pilah Bank Sampah Induk</title>
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
                    <span class="brand-role">Portal Petugas Lapangan</span>
                </div>
            </div>

            <div class="user-menu">
                <div class="user-info">
                    <span class="user-name">{{ $petugas->name }}</span>
                    <span class="user-email">{{ $petugas->email }}</span>
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
                <span class="stat-label">Antrean Penjemputan Aktif</span>
                <h2 class="stat-value">{{ $queue->count() }} Permintaan</h2>
                <p class="stat-hint">Urut tanggal jemput terdekat. Selesaikan setelah penimbangan.</p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Sampah Ditimbang Hari Ini</span>
                <h2 class="stat-value">{{ number_format($weighedToday, 1, ',', '.') }} Kg</h2>
                <p class="stat-hint">Akumulasi bobot validasi timbangan Anda hari ini.</p>
            </div>

            <div class="stat-card">
                <span class="stat-label">Saldo Dikreditkan Hari Ini</span>
                <h2 class="stat-value">Rp {{ number_format($creditedToday, 0, ',', '.') }}</h2>
                <p class="stat-hint">Total saldo warga yang masuk ke buku besar dari penimbangan Anda.</p>
            </div>
        </section>

        <section class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h3>Antrean Penjemputan Sampah</h3>
                <p>Datangi alamat warga, timbang sampah terpilah, lalu simpan hasil timbangan. Saldo warga otomatis bertambah.</p>
            </div>

            @if ($queue->isEmpty())
                <div class="empty-state">Tidak ada permintaan penjemputan yang menunggu.</div>
            @else
                <div class="pickup-list">
                    @foreach ($queue as $pickup)
                        <div class="pickup-item">
                            <div class="pickup-top">
                                <strong>{{ $pickup->user->name }} • {{ \Carbon\Carbon::parse($pickup->pickup_date)->translatedFormat('d M Y') }} {{ $pickup->time_slot }}</strong>
                                <span class="badge badge-{{ $pickup->status }}">{{ ucfirst($pickup->status) }}</span>
                            </div>
                            <p class="pickup-notes">{{ $pickup->pickup_address }}</p>
                            <p class="pickup-notes">Rincian: {{ $pickup->estimated_notes }}</p>

                            @if ($pickup->status === 'ditimbang')
                                <div class="pickup-result">
                                    @foreach ($pickup->items as $item)
                                        {{ $item->category->name }}: {{ number_format($item->weight, 2, ',', '.') }} {{ $item->category->unit }}
                                        × Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                        = Rp {{ number_format($item->subtotal, 0, ',', '.') }}<br>
                                    @endforeach
                                    Total: <strong>{{ number_format($pickup->total_weight, 2, ',', '.') }} kg</strong>
                                    | Saldo masuk: <strong>+Rp {{ number_format($pickup->total_earned, 0, ',', '.') }}</strong>
                                </div>

                                <details style="margin-top: 0.85rem;">
                                    <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600; color: #1e40af;">Ubah timbangan</summary>
                                    <form action="{{ route('petugas.pickup.reweigh', $pickup) }}" method="POST" class="form-stack" style="margin-top: 0.75rem;">
                                        @csrf
                                        <div class="form-row">
                                            @foreach ($wasteCategories as $cat)
                                                @php $existing = $pickup->items->firstWhere('waste_category_id', $cat->id); @endphp
                                                <div class="form-group">
                                                    <label for="edit-w-{{ $pickup->id }}-{{ $cat->id }}">{{ $cat->name }} (Rp {{ number_format($cat->buy_price, 0, ',', '.') }}/{{ $cat->unit }})</label>
                                                    <input type="number" id="edit-w-{{ $pickup->id }}-{{ $cat->id }}" name="weights[{{ $cat->id }}]" step="0.1" min="0.1" max="500" placeholder="0" value="{{ $existing ? (float) $existing->weight : '' }}">
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan Timbangan</button>
                                    </form>
                                </details>

                                <form action="{{ route('petugas.pickup.cancel-weigh', $pickup) }}" method="POST" style="margin-top: 0.85rem;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-ghost">Batalkan Penimbangan</button>
                                </form>

                                <form action="{{ route('petugas.pickup.finish', $pickup) }}" method="POST" style="margin-top: 0.85rem;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Selesaikan Penjemputan</button>
                                </form>
                            @elseif ($pickup->status === 'dijemput')
                                <form action="{{ route('petugas.pickup.weigh', $pickup) }}" method="POST" class="form-stack" style="margin-top: 0.85rem;">
                                    @csrf
                                    <div class="form-row">
                                        @foreach ($wasteCategories as $cat)
                                            <div class="form-group">
                                                <label for="w-{{ $pickup->id }}-{{ $cat->id }}">{{ $cat->name }} (Rp {{ number_format($cat->buy_price, 0, ',', '.') }}/{{ $cat->unit }})</label>
                                                <input type="number" id="w-{{ $pickup->id }}-{{ $cat->id }}" name="weights[{{ $cat->id }}]" step="0.1" min="0.1" max="500" placeholder="0">
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan Timbangan &amp; Kreditkan Saldo</button>
                                </form>
                            @else
                                <form action="{{ route('petugas.pickup.start', $pickup) }}" method="POST" style="margin-top: 0.85rem;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background: #dbeafe; color: #1e40af;">Mulai Jemput</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header">
                <h3>Riwayat Penimbangan Saya</h3>
                <p>Sepuluh penimbangan terakhir yang Anda selesaikan.</p>
            </div>

            @if ($history->isEmpty())
                <div class="empty-state">Belum ada penimbangan yang Anda selesaikan.</div>
            @else
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Warga</th>
                                <th>Bobot Valid</th>
                                <th>Saldo Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $row)
                                <tr>
                                    <td>{{ $row->updated_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $row->user->name }}</td>
                                    <td>{{ number_format($row->total_weight, 2, ',', '.') }} Kg</td>
                                    <td class="text-credit">+Rp {{ number_format($row->total_earned, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>
</body>
</html>

