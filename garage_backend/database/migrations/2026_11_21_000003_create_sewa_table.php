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
        Schema::create('sewa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('garage_id')->constrained('garages')->cascadeOnDelete();
            $table->enum('rental_type', ['harian', 'bulanan']);
            $table->integer('total_days');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('with_charging')->default(false);
            $table->enum('battery_input_mode', ['persen', 'kwh'])->nullable();
            $table->decimal('battery_remaining_value', 8, 2)->nullable();
            $table->decimal('kwh_charged', 8, 2)->nullable();
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sewa');
    }
};
