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
        if (Schema::hasColumn('users', 'phone_number')) {
            return; // Column already exists
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('fcm_token', 255)->nullable(); // Firebase Cloud Messaging
            $table->boolean('notification_enabled')->default(true);
            $table->string('language', 10)->default('id'); // 'id' or 'en'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'phone_verified_at',
                'fcm_token',
                'notification_enabled',
                'language'
            ]);
        });
    }
};
