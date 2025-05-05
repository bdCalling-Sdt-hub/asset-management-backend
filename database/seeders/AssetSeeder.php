<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch an organization user (can be super_admin, organization, or third_party)
        $organization = User::whereIn('role', ['super_admin', 'organization', 'third_party'])->first();
        $organizationId = $organization ? $organization->id : null;

        // Create sample assets
        Asset::create([
            'organization_id'       => $organizationId,
            'product_id'            => 'PROD-001',
            'brand'                 => 'Apple',
            'range'                 => 'Premium',
            'product'               => 'MacBook Pro',
            'qr_code'               => 'QR123456789',
            'serial_number'         => 'SN-123456789',
            'external_serial_number'=> 'EXT-987654321',
            'manufacturing_date'    => Carbon::parse('2022-01-01'),
            'installation_date'     => Carbon::parse('2022-02-01'),
            'warranty_end_date'     => Carbon::parse('2025-01-01'),
            'unit_price'            => 2500.00,
            'current_spend'         => 500.00,
            'max_spend'             => 3000.00,
            'fitness_product'       => true,
            'has_odometer'          => false,
            'location'              => 'Dhaka, Bangladesh',
            'residual_price'        => 1500.00,
            'created_at'            => Carbon::now(),
            'updated_at'            => Carbon::now(),
        ]);

        Asset::create([
            'organization_id'       => $organizationId,
            'product_id'            => 'PROD-002',
            'brand'                 => 'Samsung',
            'range'                 => 'Mid-Range',
            'product'               => 'Samsung Galaxy S21',
            'qr_code'               => 'QR987654321',
            'serial_number'         => 'SN-987654321',
            'external_serial_number'=> 'EXT-123456789',
            'manufacturing_date'    => Carbon::parse('2021-06-01'),
            'installation_date'     => Carbon::parse('2021-07-01'),
            'warranty_end_date'     => Carbon::parse('2024-06-01'),
            'unit_price'            => 1200.00,
            'current_spend'         => 300.00,
            'max_spend'             => 2000.00,
            'fitness_product'       => false,
            'has_odometer'          => false,
            'location'              => 'Chattogram, Bangladesh',
            'residual_price'        => 800.00,
            'created_at'            => Carbon::now(),
            'updated_at'            => Carbon::now(),
        ]);
    }
}
