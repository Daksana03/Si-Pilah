<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Beras Premium 5kg, Minyak Goreng 2L, Pupuk Kompos 5kg
            $table->decimal('price', 12, 2); // Nilai tukar saldo
            $table->integer('stock')->default(0);
            $table->string('unit')->default('paket');
            $table->timestamps();
        });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodities');
    }
};
