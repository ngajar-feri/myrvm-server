<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_name', 100); // 'yolo11', 'sam2'
            $table->string('version', 50); // e.g., 'v3.1', '2024-01-10'
            $table->text('file_path'); // MinIO path to best.pt
            $table->decimal('file_size_mb', 10, 2)->nullable();
            $table->string('sha256_hash', 64); // Hash verification
            $table->unsignedBigInteger('training_job_id')->nullable(); // Will reference cv_training_jobs in future
            $table->json('metrics')->nullable(); // Performance metrics (mAP, loss, etc.)
            $table->boolean('is_active')->default(false); // Currently deployed version
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['model_name', 'version']);

            // Indexes
            $table->index('model_name');
            $table->index('is_active');
            $table->index('sha256_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_versions');
    }
};
