<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\Commodity;
use App\Models\LedgerMutation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
            public function run(): void
            {
                WasteCategory::insert([
            ['name' => 'Botol Plastik PET Bersih', 'unit' => 'kg', 'buy_price' => 3500, 'sell_price' => 5000, 'description' => 'Botol air mineral tanpa label & tutup', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kardus & Kertas Karton', 'unit' => 'kg', 'buy_price' => 2200, 'sell_price' => 3200, 'description' => 'Kardus kering terlipat rapi', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kaleng Aluminium (Minuman)', 'unit' => 'kg', 'buy_price' => 11000, 'sell_price' => 14500, 'description' => 'Kaleng soda/minuman kondisi bersih', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Minyak Jelantah (UCO)', 'unit' => 'liter', 'buy_price' => 5500, 'sell_price' => 7500, 'description' => 'Minyak goreng bekas tersaring dalam jerigen', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Commodity::insert([
            ['name' => 'Beras Medium 5 Kg', 'price' => 65000, 'stock' => 20, 'unit' => 'karung', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Minyak Goreng Sawit 2 Liter', 'price' => 34000, 'stock' => 15, 'unit' => 'pouch', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gula Pasir Kristal 1 Kg', 'price' => 17500, 'stock' => 30, 'unit' => 'bungkus', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pupuk Kompos Organik 5 Kg', 'price' => 15000, 'stock' => 25, 'unit' => 'karung', 'created_at' => now(), 'updated_at' => now()],
        ]);

        User::create([
            'name' => 'Admin Bank Sampah',
            'email' => 'admin@sipilah.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Budi Petugas',
            'email' => 'petugas@sipilah.test',
            'password' => Hash::make('password123'),
            'role' => 'petugas',
        ]);

        User::create([
            'name' => 'Siti Warga',
            'email' => 'warga@sipilah.test',
            'password' => Hash::make('password123'),
            'role' => 'warga',
        ]);
    }
}