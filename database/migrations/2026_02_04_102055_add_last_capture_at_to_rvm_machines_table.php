<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->timestamp('last_capture_at')->nullable()->after('last_ping');
        });
    }

    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->dropColumn('last_capture_at');
        });
    }
};
