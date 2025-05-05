<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InspectionSheet;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;

class InspectionSheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch an existing ticket
        $ticket = Ticket::first();
        $ticketId = $ticket ? $ticket->id : null;

        // Fetch an existing support agent (assuming role-based users)
        $supportAgent = User::where('role', 'support_agent')->first();
        $supportAgentId = $supportAgent ? $supportAgent->id : null;

        // Fetch an existing technician
        $technician = User::where('role', 'technician')->first();
        $technicianId = $technician ? $technician->id : null;

        // Ensure we have valid foreign keys before creating records
        if (!$ticketId || !$supportAgentId || !$technicianId) {
            return;
        }

        // Sample Inspection Sheets
        $inspectionSheets = [
            [
                'ticket_id'                  => $ticketId,
                'support_agent_id'           => $supportAgentId,
                'technician_id'              => $technicianId,
                'inspection_sheet_type'      => 'New Sheets',
                'support_agent_comment'      => 'Initial inspection completed, minor issues detected.',
                'technician_comment'         => 'Repaired the wiring issue and tested the functionality.',
                'location_employee_signature'=> 'signature_image.png',
                'image'                      => json_encode(['inspection1.jpg', 'inspection2.jpg']),
                'video'                      => json_encode(['inspection_video.mp4']),
                'status'                     => 'Completed',
                'created_at'                 => Carbon::now(),
                'updated_at'                 => Carbon::now(),
            ],
            [
                'ticket_id'                  => $ticketId,
                'support_agent_id'           => $supportAgentId,
                'technician_id'              => $technicianId,
                'inspection_sheet_type'      => 'Open Sheets',
                'support_agent_comment'      => 'Reported issue with the heating system.',
                'technician_comment'         => 'Replaced faulty parts and restored system functionality.',
                'location_employee_signature'=> 'signature_image2.png',
                'image'                      => json_encode(['repair1.jpg']),
                'video'                      => json_encode(['repair_video.mp4']),
                'status'                     => 'Pending Approval',
                'created_at'                 => Carbon::now(),
                'updated_at'                 => Carbon::now(),
            ],
            [
                'ticket_id'                  => $ticketId,
                'support_agent_id'           => $supportAgentId,
                'technician_id'              => $technicianId,
                'inspection_sheet_type'      => 'Past Sheets',
                'support_agent_comment'      => 'Follow-up inspection to ensure proper functioning.',
                'technician_comment'         => 'No issues detected, everything is working fine.',
                'location_employee_signature'=> 'signature_image3.png',
                'image'                      => json_encode(['past_inspection1.jpg']),
                'video'                      => json_encode(['past_inspection_video.mp4']),
                'status'                     => 'Closed',
                'created_at'                 => Carbon::now(),
                'updated_at'                 => Carbon::now(),
            ],
        ];

        // Insert data
        foreach ($inspectionSheets as $sheet) {
            InspectionSheet::create($sheet);
        }
    }
}
