<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = DB::table('users')->where('role', 'tenan')->first()->id;

        // Delete existing vouchers for this tenant
        Voucher::where('tenant_id', $tenantId)->delete();

        // Create vouchers matching Voucher model fields
        Voucher::create([
            'tenant_id' => $tenantId,
            'title' => 'Diskon 50% Minuman',
            'description' => 'Dapatkan diskon 50% untuk semua minuman di Starbucks',
            'code' => 'DRINK50',
            'points_required' => 500,
            'valid_until' => now()->addMonths(3),
            'status' => 'active',
        ]);

        Voucher::create([
            'tenant_id' => $tenantId,
            'title' => 'Gratis 1 Pastry',
            'description' => 'Gratis 1 pastry pilihan dengan pembelian 2 minuman',
            'code' => 'FREEPASTRY',
            'points_required' => 300,
            'valid_until' => now()->addMonths(2),
            'status' => 'active',
        ]);

        Voucher::create([
            'tenant_id' => $tenantId,
            'title' => 'Cashback Rp 20.000',
            'description' => 'Cashback Rp 20.000 untuk pembelian minimal Rp 100.000',
            'code' => 'CASHBACK20',
            'points_required' => 200,
            'valid_until' => now()->addMonths(1),
            'status' => 'active',
        ]);

        Voucher::create([
            'tenant_id' => $tenantId,
            'title' => 'Buy 1 Get 1 Coffee',
            'description' => 'Beli 1 gratis 1 untuk semua varian kopi',
            'code' => 'BOGO-COFFEE',
            'points_required' => 800,
            'valid_until' => now()->addWeeks(2),
            'status' => 'active',
        ]);

        $this->command->info('✅ Created 4 vouchers');
    }
}
