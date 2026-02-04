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
        Schema::create('dataset_images_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines')->onDelete('cascade');
            $table->string('file_path');
            $table->string('filename');
            $table->string('camera_port')->nullable();
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_images_raw');
    }
};
