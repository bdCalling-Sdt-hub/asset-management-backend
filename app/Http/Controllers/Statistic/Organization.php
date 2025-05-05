<?php
namespace App\Http\Controllers\Statistic;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\InspectionSheet;
use App\Models\JobCard;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Organization extends Controller
{
    public function dashboard()
    {
        // get total asset
        $total_asset = Asset::where('organization_id', Auth::id())->count();
        // get total ticket
        $collect_asset_id = Asset::where('organization_id', Auth::id())->pluck('id');
        $total_ticket     = Ticket::whereIn('asset_id', $collect_asset_id)->count();
        // get total job card
        $collect_ticket_id           = Ticket::whereIn('asset_id', $collect_asset_id)->pluck('id');
        $collect_inspection_sheet_id = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->pluck('id');
        $total_job_card              = JobCard::whereIn('inspection_sheet_id', $collect_inspection_sheet_id)->count();
        // get total inspection sheet
        $total_inspection_sheet = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->count();
        // ticket_status
        $ticket_data = Ticket::whereIn('asset_id', $collect_asset_id)->select('ticket_status as tickets', DB::raw('COUNT(*) as quantity'))
            ->groupBy('ticket_status')
            ->get();
        $totalTickets = Ticket::whereIn('asset_id', $collect_asset_id)->count();
        // warranty_details
        $daysThreshold = 90;
        $expiringSoon  = DB::table('assets')
            ->select('product', DB::raw('MIN(DATEDIFF(warranty_end_date, NOW())) as expiry_days'))
            ->where('organization_id', Auth::id())
            ->whereDate('warranty_end_date', '>=', now())
            ->whereDate('warranty_end_date', '<=', now()->addDays($daysThreshold))
            ->groupBy('product')
            ->orderBy('expiry_days', 'ASC')
            ->get();

        $expiringSoon->transform(function ($item) {
            if ($item->expiry_days <= 1) {
                $item->expiry_text = "Expires today";
            } elseif ($item->expiry_days < 30) {
                $item->expiry_text = "Expires in {$item->expiry_days} days";
            } else {
                $months            = floor($item->expiry_days / 30);
                $item->expiry_text = "Expires in {$months} month" . ($months > 1 ? 's' : '');
            }
            unset($item->expiry_days);
            return $item;
        });

        $totalExpiringSoon = DB::table('assets')
            ->where('organization_id', Auth::id())
            ->whereDate('warranty_end_date', '>=', now())
            ->whereDate('warranty_end_date', '<=', now()->addDays($daysThreshold))
            ->count();
        // out of order assets
        $expiredWarranties = Asset::where('organization_id', Auth::id())->whereDate('warranty_end_date', '<', now())
            ->select('product as asset', DB::raw('COUNT(*) as quantity'))
            ->groupBy('product')
            ->get();

        $totalExpired = Asset::where('organization_id', Auth::id())->whereDate('warranty_end_date', '<', now())->count();

        $data = [
            'total_asset'             => $total_asset,
            'total_ticket'            => $total_ticket,
            'total_job_card'          => $total_job_card,
            'total_inspection_sheet'  => $total_inspection_sheet,
            'out_of_order_assets    ' => [
                'total'      => $totalExpired,
                'asset_data' => $expiredWarranties,
            ],
            'warranty_details'        => [
                'total'      => $totalExpiringSoon,
                'asset_data' => $expiringSoon,
            ],
            'ticket_status'           => [
                'total'       => $totalTickets,
                'ticket_data' => $ticket_data,
            ],
        ];
        return response()->json([
            'status'  => true,
            'message' => 'Data retrieve successfully',
            'data'    => $data,
        ]);
    }

    public function ticketActivity(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        $collect_asset_id = Asset::where('organization_id', Auth::id())->pluck('id');
        // Group tickets by date and status
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, ticket_status, COUNT(*) as count")
            ->whereIn('asset_id', $collect_asset_id)
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

    public function inspactionSheetOverview(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());
        // Convert to Carbon instances
        $startDate         = Carbon::parse($startDate)->startOfDay();
        $endDate           = Carbon::parse($endDate)->endOfDay();
        $collect_asset_id  = Asset::where('organization_id', Auth::id())->pluck('id');
        $collect_ticket_id = Ticket::whereIn('asset_id', $collect_asset_id)->pluck('id');
        $totalNewSheet     = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->where('inspection_sheet_type', 'New Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalOpenSheet = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->where('inspection_sheet_type', 'Open Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPastSheet = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->where('inspection_sheet_type', 'Past Sheets')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Total Inspection Per Date
        $sheets = InspectionSheet::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, inspection_sheet_type, COUNT(*) as count")
            ->whereIn('ticket_id', $collect_ticket_id)
            ->groupBy('date', 'inspection_sheet_type')
            ->orderBy('date', 'ASC')
            ->get();

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
        // Inspection Status
        $inspections = InspectionSheet::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("status, COUNT(*) as count")
            ->whereIn('ticket_id', $collect_ticket_id)
            ->groupBy('status')
            ->get();

        return response()->json([
            'total_created_sheet'       => $totalNewSheet,
            'total_running_sheet'       => $totalOpenSheet,
            'total_completed_sheet'     => $totalPastSheet,
            'total_inspection_per_date' => $formattedData,
            'inspection_status'         => $inspections,
        ]);
    }

    public function jobCardOverview(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfDay()->toDateString());
        // Convert to Carbon instances
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

        $collect_asset_id            = Asset::where('organization_id', Auth::id())->pluck('id');
        $collect_ticket_id           = Ticket::whereIn('asset_id', $collect_asset_id)->pluck('id');
        $collect_inspection_sheet_id = InspectionSheet::whereIn('ticket_id', $collect_ticket_id)->pluck('id');

        $totalNewCard = JobCard::whereIn('inspection_sheet_id', $collect_inspection_sheet_id)->where('job_card_type', 'New Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalOpenCard = JobCard::whereIn('inspection_sheet_id', $collect_inspection_sheet_id)->where('job_card_type', 'Open Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPastCard = JobCard::whereIn('inspection_sheet_id', $collect_inspection_sheet_id)->where('job_card_type', 'Past Cards')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        //total job card per date
        $cards = JobCard::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, job_card_type, COUNT(*) as count")
            ->whereIn('inspection_sheet_id', $collect_inspection_sheet_id)
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
            ->whereIn('inspection_sheet_id', $collect_inspection_sheet_id)
            ->groupBy('job_status')
            ->orderBy('job_status', 'ASC')
            ->get();

        return response()->json([
            'total_created_card'     => $totalNewCard,
            'total_running_card'     => $totalOpenCard,
            'total_completed_card'   => $totalPastCard,
            'total_jobcard_per_date' => $formattedData,
            'card_status'            => $cards,
        ]);
    }
}
