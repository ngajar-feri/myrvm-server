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
        if (Schema::hasColumn('activity_logs', 'user_agent')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('browser')->nullable()->after('user_agent');
            $table->string('browser_version')->nullable()->after('browser');
            $table->string('platform')->nullable()->after('browser_version'); // OS: Windows, macOS, Android, iOS
            $table->string('device')->nullable()->after('platform'); // desktop, phone, tablet
            $table->string('device_name')->nullable()->after('device'); // iPhone, Samsung, Desktop
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn([
                'user_agent',
                'browser',
                'browser_version',
                'platform',
                'device',
                'device_name'
            ]);
        });
    }
};
