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
        Schema::create('telemetry_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines')->onDelete('cascade');
            $table->float('plastic_weight')->default(0);
            $table->float('aluminum_weight')->default(0);
            $table->float('glass_weight')->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('battery_level')->nullable();
            $table->float('temperature')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_data');
    }
};
