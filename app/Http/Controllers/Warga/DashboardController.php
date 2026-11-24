<?php

namespace App\Http\Controllers\Warga;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityVoucher;
use App\Models\LedgerMutation;
use App\Models\PickupRequest;
use App\Models\WasteCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wasteCategories = WasteCategory::orderBy('name')->get();
        $commodities = Commodity::where('stock', '>', 0)->orderBy('price')->get();
        $pickups = $user->pickupRequests()->take(10)->get();
        $mutations = $user->ledgerMutations()->take(15)->get();
        $vouchers = $user->vouchers()->with('commodity')->take(10)->get();

        $totalWeightSetor = PickupRequest::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->sum('total_weight');

        return view('warga.dashboard', compact(
            'user',
            'wasteCategories',
            'commodities',
            'pickups',
            'mutations',
            'vouchers',
            'totalWeightSetor'
        ));
    }

    public function storePickup(Request $request)
    {
        $validated = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'time_slot' => ['required', 'string'],
            'pickup_address' => ['required', 'string', 'min:10'],
            'estimated_notes' => ['required', 'string', 'min:5'],
        ], [
            'pickup_date.after_or_equal' => 'Tanggal penjemputan minimal hari ini.',
            'pickup_address.min' => 'Alamat penjemputan mohon diisi lengkap.',
        ]);

        $user = Auth::user();

        // Simpan alamat default user jika belum ada
        if (! $user->address) {
            $user->update(['address' => $validated['pickup_address']]);
        }

        PickupRequest::create([
            'user_id' => $user->id,
            'pickup_date' => $validated['pickup_date'],
            'time_slot' => $validated['time_slot'],
            'pickup_address' => $validated['pickup_address'],
            'estimated_notes' => $validated['estimated_notes'],
            'status' => 'menunggu',
        ]);

        return back()->with('success', 'Jadwal penjemputan sampah berhasil diajukan! Menunggu konfirmasi Admin/Petugas.');
    }

    public function redeemCommodity(Request $request)
    {
        $request->validate([
            'commodity_id' => ['required', 'exists:commodities,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $user = Auth::user();
        $commodity = Commodity::findOrFail($request->commodity_id);
        $qty = (int) $request->quantity;
        $totalCost = $commodity->price * $qty;

        if ($commodity->stock < $qty) {
            return back()->withErrors(['redeem' => 'Stok komoditas di gudang tidak mencukupi.']);
        }

        if ($user->balance < $totalCost) {
            return back()->withErrors(['redeem' => 'Saldo tabungan sampah Anda tidak mencukupi untuk penukaran ini.']);
        }

        DB::transaction(function () use ($user, $commodity, $qty, $totalCost) {
            // 1. Potong saldo user (stok gudang berkurang saat admin memvalidasi voucher)
            $newBalance = $user->balance - $totalCost;
            $user->update(['balance' => $newBalance]);

            // 2. Catat mutasi debit di Buku Besar (Ledger)
            LedgerMutation::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $totalCost,
                'balance_after' => $newBalance,
                'description' => "Penukaran {$qty}x {$commodity->name}",
            ]);

            // 3. Terbitkan Kode Voucher Unik
            CommodityVoucher::create([
                'voucher_code' => 'VCH-'.strtoupper(Str::random(6)),
                'user_id' => $user->id,
                'commodity_id' => $commodity->id,
                'quantity' => $qty,
                'total_cost' => $totalCost,
                'status' => 'aktif',
            ]);
        });

        return back()->with('success', "Berhasil menukar {$qty}x {$commodity->name}! Tunjukkan kode voucher ke Admin Pengelola untuk validasi pengambilan barang.");
    }
}
