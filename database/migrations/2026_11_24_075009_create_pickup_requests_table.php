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
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('pickup_date');
            $table->string('time_slot'); // Pagi (08:00-11:00) / Sore (14:00-17:00)
            $table->text('pickup_address');
            $table->text('estimated_notes'); // Catatan jenis & perkiraan berat sampah
            $table->enum('status', ['menunggu', 'dijadwalkan', 'dijemput', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->decimal('total_weight', 8, 2)->nullable();
            $table->decimal('total_earned', 12, 2)->nullable();
            $table->timestamps();
        });
}
};
