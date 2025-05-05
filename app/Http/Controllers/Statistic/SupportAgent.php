<?php
namespace App\Http\Controllers\Statistic;

use App\Http\Controllers\Controller;
use App\Models\InspectionSheet;
use App\Models\JobCard;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupportAgent extends Controller
{
    // chart statistics for support agent dashboard
    public function chartSupportAgent(Request $request)
    {
        $filter    = $request->query('filter', 'weekly');
        $startDate = $this->getStartDate($filter);
        $endDate   = Carbon::now()->endOfDay();

        $ticketStats = $this->getStatusStats(
            Ticket::class, 'ticket_status',
            ['New'],
            ['Assigned', 'Inspection Sheet', 'Awaiting Purchase Order', 'Job Card Created'],
            ['Completed'],
            $startDate,
            $endDate
        );

        // For Inspection Sheets:
        $inspectionStats = $this->getStatusStats(
            InspectionSheet::class,
            'status',
            ['New'],
            ['Arrived in Location', 'Contract with user', 'View the problem', 'Solve the problem'],
            ['Completed'],
            $startDate,
            $endDate
        );

        // For Job Cards:
        $jobCardStats = $this->getStatusStats(
            JobCard::class,
            'job_status',
            ['New'],
            ['Assigned', 'In Progress', 'On hold', 'To be allocated', 'Awaiting courier', 'Collected by courier', 'Parts required', 'Picking', 'To be invoiced', 'Invoiced'],
            ['Completed'],
            $startDate,
            $endDate
        );

        return response()->json([
            'filter'            => $filter,
            'ticket_status'     => $ticketStats,
            'inspections'       => $inspectionStats,
            'job_card_progress' => $jobCardStats,
        ]);
    }

    // Returns the start date based on filter
    private function getStartDate($filter)
    {
        return match ($filter) {
            'monthly' => Carbon::now()->subMonth()->startOfMonth(), // Start of last month
            'yearly' => Carbon::now()->subYear()->startOfYear(),    // Start of last year
            'weekly' => Carbon::now()->subWeek()->startOfWeek(),    // Start of last week
            default => Carbon::now()->startOfWeek(),                // Default is current week
        };
    }

    private function getStatusStats($model, $column, $newStatuses, $inProgressStatuses, $completedStatuses, $startDate, $endDate)
    {
        // Total count
        $total = $model::whereBetween('created_at', [$startDate, $endDate])->count();

        // Count for each group
        $newCount        = $model::whereIn($column, $newStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();
        $inProgressCount = $model::whereIn($column, $inProgressStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();
        $completedCount  = $model::whereIn($column, $completedStatuses)->whereBetween('created_at', [$startDate, $endDate])->count();

        // Helper to calculate percentage
        $calcPercentage = fn($count) => $total ? number_format(($count / $total) * 100, 2) : 0;

        return [
            'New'         => ['count' => $newCount, 'percentage' => $calcPercentage($newCount)],
            'In-Progress' => ['count' => $inProgressCount, 'percentage' => $calcPercentage($inProgressCount)],
            'Completed'   => ['count' => $completedCount, 'percentage' => $calcPercentage($completedCount)],
        ];
    }
    //ticket activity
    public function activityTicket(Request $request)
    {
        // Get start and end dates
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());

        // Ensure start and end dates
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        // Group tickets by date and status
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, ticket_status, COUNT(*) as count")
            ->groupBy('date', 'ticket_status')
            ->orderBy('date', 'ASC')
            ->get();

        // Format the response
        $formattedData = $tickets->groupBy('date')->map(function ($dateGroup) {
            $data = [
                'New'         => 0,
                'In progress' => 0,
                'Completed'   => 0,
            ];
            foreach ($dateGroup as $ticket) {
                if ($ticket->ticket_status === 'New') {
                    $data['New'] = $ticket->count;
                } elseif (in_array($ticket->ticket_status, ['Assigned', 'Inspection Sheet', 'Awaiting Purchase Order', 'Job Card Created'])) {
                    $data['In progress'] = $ticket->count;
                } elseif ($ticket->ticket_status === 'Completed') {
                    $data['Completed'] = $ticket->count;
                }
            }
            return $data;
        });

        return response()->json([
            'start_date' => $startDate->toDateString(),
            'end_date'   => $endDate->toDateString(),
            'tickets'    => $formattedData,
        ]);
    }
    //inspection sheet overview
    public function statisticsInspectionSheet(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());

        // Convert to Carbon instances
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        // Count sheets based on type and date range
        $totalNewSheet = InspectionSheet::where('inspection_sheet_type', 'New Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalOpenSheet = InspectionSheet::where('inspection_sheet_type', 'Open Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPastSheet = InspectionSheet::where('inspection_sheet_type', 'Past Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        //inspection sheet total inspection
        $sheets = InspectionSheet::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, inspection_sheet_type, COUNT(*) as count")
            ->groupBy('date', 'inspection_sheet_type')
            ->orderBy('date', 'ASC')
            ->get();

        // Format the response
        $formattedData = $sheets->groupBy('date')->map(function ($dateGroup) {
            $data = [
                'New Sheets'  => 0,
                'Past Sheets' => 0,

            ];
            // return $data;
            foreach ($dateGroup as $sheet) {
                if ($sheet->inspection_sheet_type === 'New Sheets') {
                    $data['New Sheets'] = $sheet->count;
                } elseif ($sheet->inspection_sheet_type === 'Past Sheets') {
                    $data['Past Sheets'] = $sheet->count;
                }
            }
            return $data;
        });
        //inspection sheet status
        $inspections = InspectionSheet::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->orderBy('status', 'ASC')
            ->get();
        return response()->json([
            'total_created_sheet'       => $totalNewSheet,
            'total_running_sheet'       => $totalOpenSheet,
            'total_Completed_sheet'     => $totalPastSheet,
            'total_inspection_per_date' => $formattedData,
            'inspections_status'        => $inspections,
        ]);
    }


    //job card overview
    public function statisticsJobCard(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());

        // Convert to Carbon instances
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();
        // Count sheets based on type and date range
        $totalNewCard = JobCard::where('job_card_type', 'New Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalOpenCard = JobCard::where('job_card_type', 'Open Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPastCard = JobCard::where('job_card_type', 'Past Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        //total job card per date
        $cards = JobCard::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, job_card_type, COUNT(*) as count")
            ->groupBy('date', 'job_card_type')
            ->orderBy('date', 'ASC')
            ->get();

        // Format the response
        $formattedData = $cards->groupBy('date')->map(function ($dateGroup) {
            $data = [
                'New Cards'  => 0,
                'Past Cards' => 0,

            ];
            // return $data;
            foreach ($dateGroup as $card) {
                if ($card->job_card_type === 'New Cards') {
                    $data['New Cards'] = $card->count;
                } elseif ($card->job_card_type === 'Past Cards') {
                    $data['Past Cards'] = $card->count;
                }
            }
            return $data;
        });
        //job card status
        $cards = JobCard::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("job_status, COUNT(*) as count")
            ->groupBy('job_status')
            ->orderBy('job_status', 'ASC')
            ->get();
        return response()->json([
            'total_created_card'      => $totalNewCard,
            'total_running_card'      => $totalOpenCard,
            'total_Completed_card'    => $totalPastCard,
            'total_job_card_per_date' => $formattedData,
            'job_status'              => $cards,
        ]);
    }


}
