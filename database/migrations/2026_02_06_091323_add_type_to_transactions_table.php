<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add transaction type column for analytics and offline sync tracking.
 * 
 * Type Values:
 * - REGULAR: Normal user transaction (default)
 * - DONATION: Guest donation transaction (online)
 * - OFFLINE_SYNC: Offline transaction synced later
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['REGULAR', 'DONATION', 'OFFLINE_SYNC'])
                  ->default('REGULAR')
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
