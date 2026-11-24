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
        Schema::create('commodity_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_code')->unique(); // Contoh: VCH-8F92A1
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commodity_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('total_cost', 12, 2);
            $table->enum('status', ['aktif', 'digunakan', 'kadaluarsa'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodity_vouchers');
    }
};
