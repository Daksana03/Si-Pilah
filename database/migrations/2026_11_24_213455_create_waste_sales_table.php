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
        Schema::create('waste_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_category_id')->constrained()->restrictOnDelete();
            $table->decimal('weight', 8, 2);
            $table->decimal('price_per_unit', 10, 2); // harga jual ke pabrik saat transaksi
            $table->decimal('total', 12, 2);
            $table->string('buyer')->nullable(); // nama pabrik / pengepul
            $table->date('sold_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_sales');
    }
};
