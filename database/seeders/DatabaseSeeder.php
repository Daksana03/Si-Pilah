<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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