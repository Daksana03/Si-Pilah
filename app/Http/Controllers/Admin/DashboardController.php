<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\CommodityVoucher;
use App\Models\PickupItem;
use App\Models\WasteCategory;
use App\Models\WasteSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $wasteCategories = WasteCategory::orderBy('name')->get();
        $commodities = Commodity::orderBy('name')->get();

        $vouchers = CommodityVoucher::with(['user', 'commodity'])
            ->where('status', 'aktif')
            ->latest()
            ->get();

        $sales = WasteSale::with('category')
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $monthlyRecap = WasteSale::orderByDesc('sold_at')->get()
            ->groupBy(fn (WasteSale $sale) => $sale->sold_at->format('Y-m'))
            ->map(fn ($rows, $month) => [
                'month' => $month,
                'weight' => (float) $rows->sum('weight'),
                'total' => (float) $rows->sum('total'),
            ])
            ->take(6)
            ->values();

        return view('admin.dashboard', [
            'wasteCategories' => $wasteCategories,
            'commodities' => $commodities,
            'vouchers' => $vouchers,
            'sales' => $sales,
            'monthlyRecap' => $monthlyRecap,
            'totalWasteStock' => (float) $wasteCategories->sum('stock'),
            'totalCommodityStock' => (int) $commodities->sum('stock'),
            'voucherPending' => $vouchers->count(),
            'monthRevenue' => (float) WasteSale::whereYear('sold_at', now()->year)
                ->whereMonth('sold_at', now()->month)
                ->sum('total'),
        ]);
    }

    public function storeCategory(Request $request)
    {
        WasteCategory::create($this->categoryData($request));

        return back()->with('success', 'Kategori sampah baru ditambahkan.');
    }

    public function updateCategory(Request $request, WasteCategory $category)
    {
        $category->update($this->categoryData($request));

        return back()->with('success', 'Kategori sampah diperbarui. Harga baru berlaku untuk transaksi berikutnya.');
    }

    public function destroyCategory(WasteCategory $category)
    {
        if (PickupItem::where('waste_category_id', $category->id)->exists()
            || WasteSale::where('waste_category_id', $category->id)->exists()) {
            return back()->withErrors(['category' => 'Kategori sudah dipakai transaksi, tidak bisa dihapus.']);
        }

        $category->delete();

        return back()->with('success', 'Kategori sampah dihapus.');
    }

    public function storeCommodity(Request $request)
    {
        Commodity::create($this->commodityData($request));

        return back()->with('success', 'Komoditas baru ditambahkan ke katalog.');
    }

    public function updateCommodity(Request $request, Commodity $commodity)
    {
        $commodity->update($this->commodityData($request));

        return back()->with('success', 'Komoditas diperbarui.');
    }

    public function destroyCommodity(Commodity $commodity)
    {
        if ($commodity->vouchers()->exists()) {
            return back()->withErrors(['commodity' => 'Komoditas sudah pernah ditukar warga, tidak bisa dihapus.']);
        }

        $commodity->delete();

        return back()->with('success', 'Komoditas dihapus dari katalog.');
    }

    public function validateVoucher(CommodityVoucher $voucher)
    {
        if ($voucher->status !== 'aktif') {
            return back()->withErrors(['voucher' => 'Voucher ini sudah pernah diproses.']);
        }

        $commodity = $voucher->commodity;

        if ($commodity->stock < $voucher->quantity) {
            return back()->withErrors(['voucher' => 'Stok gudang tidak mencukupi untuk memvalidasi voucher '.$voucher->voucher_code.'.']);
        }

        DB::transaction(function () use ($voucher, $commodity) {
            $commodity->decrement('stock', $voucher->quantity);
            $voucher->update(['status' => 'digunakan']);
        });

        return back()->with('success', 'Voucher '.$voucher->voucher_code.' divalidasi. Stok '.$commodity->name.' berkurang '.$voucher->quantity.' '.$commodity->unit.'.');
    }

    public function storeSale(Request $request)
    {
        $validated = $request->validate([
            'waste_category_id' => ['required', 'exists:waste_categories,id'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'buyer' => ['nullable', 'string', 'max:100'],
            'sold_at' => ['required', 'date'],
        ], [
            'waste_category_id.required' => 'Pilih kategori limbah yang dijual.',
            'weight.min' => 'Berat penjualan minimal 0,01.',
        ]);

        $category = WasteCategory::findOrFail($validated['waste_category_id']);
        $weight = round((float) $validated['weight'], 2);

        if ((float) $category->stock < $weight) {
            return back()->withErrors(['weight' => 'Stok terkumpul '.$category->name.' hanya '.number_format((float) $category->stock, 2, ',', '.').' '.$category->unit.'.']);
        }

        $total = round((float) $validated['price_per_unit'] * $weight, 2);

        DB::transaction(function () use ($category, $validated, $weight, $total) {
            WasteSale::create([
                'waste_category_id' => $category->id,
                'weight' => $weight,
                'price_per_unit' => $validated['price_per_unit'],
                'total' => $total,
                'buyer' => $validated['buyer'] ?? null,
                'sold_at' => $validated['sold_at'],
            ]);

            $category->decrement('stock', $weight);
        });

        return back()->with('success', 'Penjualan ke pabrik dicatat: '.number_format($weight, 2, ',', '.').' '.$category->unit.' '.$category->name.', kas masuk Rp '.number_format($total, 0, ',', '.').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function commodityData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
        ], [
            'name.required' => 'Nama komoditas wajib diisi.',
        ]);
    }
}
