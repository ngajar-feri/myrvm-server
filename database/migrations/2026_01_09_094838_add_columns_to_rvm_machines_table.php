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
        Schema::table('rvm_machines', function (Blueprint $table) {
            // Cek apakah kolom sudah ada sebelum menambah (opsional, tapi aman)
            if (!Schema::hasColumn('rvm_machines', 'name')) {
                $table->string('name')->after('id')->nullable();
            }
            if (!Schema::hasColumn('rvm_machines', 'last_ping')) {
                $table->timestamp('last_ping')->nullable()->after('capacity_percentage');
            }
            // Tambahkan kolom lain jika perlu, misal rename location_name ke location
            if (Schema::hasColumn('rvm_machines', 'location_name') && !Schema::hasColumn('rvm_machines', 'location')) {
                 $table->renameColumn('location_name', 'location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->dropColumn(['name', 'last_ping']);
            if (Schema::hasColumn('rvm_machines', 'location')) {
                $table->renameColumn('location', 'location_name');
            }
        });
    }
};
