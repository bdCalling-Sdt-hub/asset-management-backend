<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobCard;
use App\Models\Ticket;
use App\Models\User;
use App\Models\InspectionSheet;
use Carbon\Carbon;

class JobCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch an existing user (support agent)
        $user = User::where('role', 'support_agent')->first();
        $userId = $user ? $user->id : null;

        // Fetch an existing ticket
        $ticket = Ticket::first();
        $ticketId = $ticket ? $ticket->id : null;

        // Fetch an existing inspection sheet
        $inspectionSheet = InspectionSheet::first();
        $inspectionSheetId = $inspectionSheet ? $inspectionSheet->id : null;

        // Ensure we have valid foreign keys before creating records
        if (!$userId || !$ticketId || !$inspectionSheetId) {
            return;
        }

        // Sample Job Cards
        $jobCards = [
            [
                'user_id'                  => $userId,
                'ticket_id'                => $ticketId,
                'inspection_sheet_id'      => $inspectionSheetId,
                'job_card_type'            => 'New Cards',
                'support_agent_comment'    => 'New job card created for the inspection process.',
                'technician_comment'       => 'Technician assigned to handle the issue.',
                'location_employee_signature' => 'employee_signature.png',
                'job_status'               => 'New',
                'created_at'               => Carbon::now(),
                'updated_at'               => Carbon::now(),
            ],
            [
                'user_id'                  => $userId,
                'ticket_id'                => $ticketId,
                'inspection_sheet_id'      => $inspectionSheetId,
                'job_card_type'            => 'Open Cards',
                'support_agent_comment'    => 'Job card opened for further investigation.',
                'technician_comment'       => 'Inspection in progress, awaiting parts for replacement.',
                'location_employee_signature' => 'employee_signature2.png',
                'job_status'               => 'In Progress',
                'created_at'               => Carbon::now(),
                'updated_at'               => Carbon::now(),
            ],
            [
                'user_id'                  => $userId,
                'ticket_id'                => $ticketId,
                'inspection_sheet_id'      => $inspectionSheetId,
                'job_card_type'            => 'Past Cards',
                'support_agent_comment'    => 'Job card closed, issue resolved.',
                'technician_comment'       => 'System working as expected, no further issues.',
                'location_employee_signature' => 'employee_signature3.png',
                'job_status'               => 'Completed',
                'created_at'               => Carbon::now(),
                'updated_at'               => Carbon::now(),
            ]
        ];

        // Insert data
        foreach ($jobCards as $card) {
            JobCard::create($card);
        }
    }
}
