<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\LedgerMutation;
use App\Models\PickupItem;
use App\Models\PickupRequest;
use App\Models\WasteCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $petugas = Auth::user();

        $queue = PickupRequest::with(['user', 'items.category'])
            ->whereIn('status', ['menunggu', 'dijadwalkan', 'dijemput', 'ditimbang'])
            ->orderBy('pickup_date')
            ->get();

        $history = PickupRequest::with('user')
            ->where('petugas_id', $petugas->id)
            ->where('status', 'selesai')
            ->latest()
            ->take(10)
            ->get();

        $wasteCategories = WasteCategory::orderBy('name')->get();

        $todayItems = PickupItem::whereDate('pickup_items.created_at', today())
            ->whereHas('pickup', fn ($query) => $query->where('petugas_id', $petugas->id));

        $weighedToday = (clone $todayItems)->sum('weight');
        $creditedToday = (clone $todayItems)->sum('subtotal');

        return view('petugas.dashboard', compact(
            'petugas',
            'queue',
            'history',
            'wasteCategories',
            'weighedToday',
            'creditedToday'
        ));
    }

    public function start(PickupRequest $pickup)
    {
        if (! $this->mayHandle($pickup, ['menunggu', 'dijadwalkan'])) {
            return back()->withErrors(['pickup' => 'Permintaan ini sudah diproses petugas lain atau statusnya tidak valid.']);
        }

        $pickup->update([
            'petugas_id' => Auth::id(),
            'status' => 'dijemput',
        ]);

        return back()->with('success', 'Penjemputan untuk '.$pickup->user->name.' dimulai. Lanjutkan dengan menimbang sampah.');
    }

    public function weigh(Request $request, PickupRequest $pickup)
    {
        if (! $this->mayHandle($pickup, ['dijemput'])) {
            return back()->withErrors(['pickup' => 'Tekan "Mulai Jemput" dulu sebelum menimbang, atau permintaan ini sudah tersimpan/dipegang petugas lain.']);
        }

        $weights = $this->parseWeights($request);

        DB::transaction(fn () => $this->applyWeighing($pickup, $weights));

        $pickup->refresh();

        return back()->with('success', 'Timbangan tersimpan: '.number_format((float) $pickup->total_weight, 2, ',', '.').' kg, saldo warga masuk Rp '.number_format((float) $pickup->total_earned, 0, ',', '.').'. Periksa dulu, lalu tekan "Selesaikan Penjemputan".');
    }

    public function reweigh(Request $request, PickupRequest $pickup)
    {
        if (! $this->mayHandle($pickup, ['ditimbang'])) {
            return back()->withErrors(['pickup' => 'Hanya penimbangan yang sudah tersimpan dan belum diselesaikan yang dapat diubah.']);
        }

        $weights = $this->parseWeights($request);

        DB::transaction(function () use ($pickup, $weights) {
            $this->reverseWeighing($pickup, 'Koreksi penimbangan');
            $this->applyWeighing($pickup, $weights);
        });

        return back()->with('success', 'Timbangan diperbarui. Saldo warga dan stok gudang dihitung ulang.');
    }

    public function cancelWeigh(PickupRequest $pickup)
    {
        if (! $this->mayHandle($pickup, ['ditimbang'])) {
            return back()->withErrors(['pickup' => 'Hanya penimbangan yang sudah tersimpan dan belum diselesaikan yang dapat dibatalkan.']);
        }

        DB::transaction(function () use ($pickup) {
            $this->reverseWeighing($pickup, 'Pembatalan penimbangan');

            $pickup->update([
                'status' => 'dijemput',
                'total_weight' => null,
                'total_earned' => null,
            ]);
        });

        return back()->with('success', 'Penimbangan dibatalkan. Saldo warga dan stok gudang dikembalikan, silakan timbang ulang.');
    }

    public function finish(PickupRequest $pickup)
    {
        if (! $this->mayHandle($pickup, ['ditimbang'])) {
            return back()->withErrors(['pickup' => 'Hanya penjemputan berstatus ditimbang yang dapat diselesaikan.']);
        }

        $pickup->update(['status' => 'selesai']);

        return back()->with('success', 'Penjemputan #'.$pickup->id.' selesai dan tercatat di buku besar warga.');
    }

    /**
     * @return array<int, float>
     *
     * @throws ValidationException
     */
    private function parseWeights(Request $request): array
    {
        $validated = $request->validate([
            'weights' => ['array'],
            'weights.*' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
        ], [
            'weights.*.min' => 'Berat per kategori minimal 0,1 kg.',
        ]);

        $weights = collect($validated['weights'] ?? [])
            ->map(fn ($weight) => round((float) $weight, 2))
            ->filter(fn ($weight) => $weight > 0)
            ->all();

        if ($weights === []) {
            throw ValidationException::withMessages(['weights' => 'Isi minimal satu kategori sampah dengan berat lebih dari 0 kg.']);
        }

        $found = WasteCategory::whereIn('id', array_keys($weights))->count();

        if ($found !== count($weights)) {
            throw ValidationException::withMessages(['weights' => 'Kategori sampah yang dipilih tidak ditemukan.']);
        }

        return $weights;
    }

    /**
     * @param  array<int, float>  $weights
     */
    private function applyWeighing(PickupRequest $pickup, array $weights): void
    {
        $categories = WasteCategory::whereIn('id', array_keys($weights))->get()->keyBy('id');

        $totalWeight = 0.0;
        $totalEarned = 0.0;

        foreach ($weights as $categoryId => $weight) {
            $category = $categories[$categoryId];
            $unitPrice = (float) $category->buy_price; // harga dikunci saat transaksi
            $subtotal = round($unitPrice * $weight, 2);

            PickupItem::create([
                'pickup_request_id' => $pickup->id,
                'waste_category_id' => $category->id,
                'weight' => $weight,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            $category->increment('stock', $weight);

            $totalWeight += $weight;
            $totalEarned += $subtotal;
        }

        $warga = $pickup->user;
        $newBalance = (float) $warga->balance + $totalEarned;

        $pickup->update([
            'petugas_id' => Auth::id(),
            'status' => 'ditimbang',
            'total_weight' => round($totalWeight, 2),
            'total_earned' => $totalEarned,
        ]);

        $warga->update(['balance' => $newBalance]);

        LedgerMutation::create([
            'user_id' => $warga->id,
            'type' => 'kredit',
            'amount' => $totalEarned,
            'balance_after' => $newBalance,
            'description' => sprintf('Setoran sampah %.2f kg (penjemputan #%d)', $totalWeight, $pickup->id),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function reverseWeighing(PickupRequest $pickup, string $reason): void
    {
        $warga = $pickup->user;
        $refund = (float) $pickup->total_earned;

        if ((float) $warga->balance < $refund) {
            throw ValidationException::withMessages([
                'pickup' => 'Saldo warga sudah terpakai untuk penukaran, koreksi/pembatalan tidak dapat dilakukan.',
            ]);
        }

        foreach ($pickup->items()->with('category')->get() as $item) {
            $item->category->decrement('stock', (float) $item->weight);
            $item->delete();
        }

        if ($refund > 0) {
            $newBalance = (float) $warga->balance - $refund;
            $warga->update(['balance' => $newBalance]);

            LedgerMutation::create([
                'user_id' => $warga->id,
                'type' => 'debit',
                'amount' => $refund,
                'balance_after' => $newBalance,
                'description' => sprintf('%s (penjemputan #%d)', $reason, $pickup->id),
            ]);
        }
    }

    /**
     * Status harus diizinkan dan permintaan belum dipegang petugas lain.
     *
     * @param  array<int, string>  $allowed
     */
    private function mayHandle(PickupRequest $pickup, array $allowed): bool
    {
        if (! in_array($pickup->status, $allowed, true)) {
            return false;
        }

        return $pickup->petugas_id === null || $pickup->petugas_id === Auth::id();
    }
}
