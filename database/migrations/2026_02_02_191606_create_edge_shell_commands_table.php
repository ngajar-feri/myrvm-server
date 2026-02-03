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
        Schema::create('edge_shell_commands', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('command');
            $table->string('category'); // system, logs, network, service
            $table->text('description')->nullable();
            $table->boolean('is_dangerous')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edge_shell_commands');
    }
};
