<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch an existing user
        $user = User::first();
        $userId = $user ? $user->id : null;

        // Fetch an existing asset
        $asset = Asset::first();
        $assetId = $asset ? $asset->id : null;

        // Ensure we have valid user and asset before creating tickets
        if (!$userId || !$assetId) {
            return;
        }

        Ticket::create([
            'user_id'       => $userId,
            'asset_id'      => $assetId,
            'ticket_type'   => 'Maintenance',
            'problem'       => 'The device is overheating frequently.',
            'user_comment'  => 'Please resolve this as soon as possible.',
            'ticket_status' => 'Open',
            'cost'          => '1500',
            'order_number'  => 'ORD-20240317-001',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ]);

        Ticket::create([
            'user_id'       => $userId,
            'asset_id'      => $assetId,
            'ticket_type'   => 'Repair',
            'problem'       => 'The display screen is not working.',
            'user_comment'  => 'Need urgent repair.',
            'ticket_status' => 'In Progress',
            'cost'          => '2500',
            'order_number'  => 'ORD-20240317-002',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ]);
    }
}
