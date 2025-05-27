<?php
namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\InspectionSheet;
use App\Models\JobCard;
use App\Models\Report;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    //create report for organization
    public function addReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_card_id' => 'nullable|string|exists:job_cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        if (! $request->job_card_id) {
            return response()->json(['status' => false, 'message' => 'At least one ID (Job Card) is required'], 400);
        }

        $reportType = $request->job_card_id ? 'job_card' : null;

        $report = Report::create([
            'user_id'     => Auth::id(), // Get authenticated user ID
            'job_card_id' => $request->job_card_id,
            'report_type' => $reportType,
        ]);

        return response()->json(['status' => true, 'message' => 'Report created successfully', 'report' => $report]);
    }
    //get report
    public function reportDetails($id)
    {

        $reports = Report::with([
            //jobcard
            'jobCard:id,inspection_sheet_id,job_status,support_agent_comment,technician_comment',
            'jobCard.inspectionSheet:id,ticket_id,technician_id',
            'jobCard.inspectionSheet.technician:id,name,image',
            'jobCard.inspectionSheet.ticket:id,problem,asset_id',
            'jobCard.inspectionSheet.ticket.asset:id,product,location,organization_id',
            'jobCard.inspectionSheet.ticket.asset.organization:id,name,address,phone',
        ])->find($id);

        // return $reports;

        // Check if reports are empty first
        if (! $reports) {
            return response()->json(['status' => true, 'message' => 'No reports found'], 200);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reports retrieved successfully',
            'reports' => $reports,
        ]);
    }

    //create report for super admin
    public function createReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id'            => 'nullable|string|exists:assets,id',
            'ticket_id'           => 'nullable|string|exists:tickets,id',
            'inspection_sheet_id' => 'nullable|string|exists:inspection_sheets,id',
            'job_card_id'         => 'nullable|string|exists:job_cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        if (! $request->asset_id && ! $request->ticket_id && ! $request->inspection_sheet_id && ! $request->job_card_id) {
            return response()->json(['status' => false, 'message' => 'At least one ID (Job Card, Asset, Ticket, or Inspection) is required'], 400);
        }

        $reportType = $request->job_card_id ? 'job_card' :
        ($request->asset_id ? 'asset' :
            ($request->inspection_sheet_id ? 'inspection' : 'ticket'));

        $report = Report::create([
            'user_id'             => Auth::id(), // Get authenticated user ID
            'asset_id'            => $request->asset_id,
            'ticket_id'           => $request->ticket_id,
            'inspection_sheet_id' => $request->inspection_sheet_id,
            'job_card_id'         => $request->job_card_id,
            'report_type'         => $reportType,
        ]);

        return response()->json(['status' => true, 'message' => 'Report created successfully', 'report' => $report]);
    }
    public function listReports()
    {
        $reports = Report::with([
            //jobcard
            'jobCard:id,inspection_sheet_id',
            'jobCard.inspectionSheet:id,ticket_id',
            'jobCard.inspectionSheet.ticket:id,problem,asset_id',
            'jobCard.inspectionSheet.ticket.asset:id,product,location,organization_id',
            'jobCard.inspectionSheet.ticket.asset.organization:id,name,address',
            //inspectionsheet
            'inspectionSheet:id,ticket_id',
            'inspectionSheet.ticket:id,problem,asset_id',
            'inspectionSheet.ticket.asset:id,product,location,organization_id',
            'inspectionSheet.ticket.asset.organization:id,name,address',
            //ticket
            'ticket:id,problem,asset_id',
            'ticket.asset:id,product,location,organization_id',
            'ticket.asset.organization:id,name,address',
            //asset
            'asset:id,product,location,organization_id',
            'asset.organization:id,name,address',
        ])
            ->get();

        // Check if reports are empty first
        if ($reports->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No reports found'], 404);
        }

        // Debug the reports output to check if inspection data is loaded
        foreach ($reports as $report) {
            if ($report->inspection) {
                Log::info("Inspection data: ", $report->inspection->toArray());
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reports retrieved successfully',
            'reports' => $reports,
        ]);
    }
    //report details
    public function detailsReports($id)
    {

        $reports = Report::with([
            //jobcard
            'jobCard:id,inspection_sheet_id,job_status,support_agent_comment,technician_comment',
            'jobCard.inspectionSheet:id,ticket_id,technician_id',
            'jobCard.inspectionSheet.technician:id,name,image',
            'jobCard.inspectionSheet.ticket:id,asset_id',
            'jobCard.inspectionSheet.ticket.asset:id,product,location,organization_id',
            'jobCard.inspectionSheet.ticket.asset.organization:id,name,address,phone',
            //inspectionsheet
            'inspectionSheet:id,ticket_id,technician_id,support_agent_comment,technician_comment',
            'inspectionSheet.technician:id,name,image',
            'inspectionSheet.ticket:id,asset_id',
            'inspectionSheet.ticket.asset:id,product,location,organization_id',
            'inspectionSheet.ticket.asset.organization:id,name,address,phone',
            //ticket
            'ticket:id,asset_id,user_comment',
            'ticket.asset:id,product,location,organization_id',
            'ticket.asset.organization:id,name,address,phone',
            //asset
            'asset:id,product,location,organization_id',
            'asset.organization:id,name,address,phone',
        ])->find($id);

        // return $reports;

        // Check if reports are empty first
        if (! $reports) {
            return response()->json(['status' => true, 'message' => 'No reports found'], 200);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reports retrieved successfully',
            'reports' => $reports,
        ]);
    }

    // public function makeReport(Request $request)
    // {

    //     $data = null;

    //     if ($request->ticket_id) {
    //         $ticket = Ticket::with(
    //             'user:id,name,email,address,role,phone,image,status',
    //             'asset.organization:id,name,email,address,role,phone,image,status',
    //             'assigned_user:id,name,email,address,role,phone,image,status'
    //         )->where('order_number', $request->ticket_id)->first();

    //         $data = [
    //             'report_id'      => $ticket->order_number,
    //             'ticket_id'      => $ticket->order_number,
    //             'organization'   => $ticket->asset->organization->name ?? 'N/A',
    //             'date_created'   => now()->format('m/d/Y H:i:s A'),
    //             'ticket_details' => [
    //                 'make_and_model'    => $ticket->asset->product ?? 'N/A',
    //                 'serial_number'     => $ticket->asset->serial_number ?? 'N/A',
    //                 'fault_description' => $ticket->problem ?? 'N/A',
    //                 'ticket'            => $ticket->order_number ?? 'N/A',
    //             ],
    //             'contact'        => $ticket->asset->brand ?? 'N/A',
    //             'status'         => $ticket->ticket_status ?? 'N/A',
    //             'technician'     => $ticket->assigned_user->name ?? 'Unassigned',
    //             'started'        => $ticket->ticket_type == 'New Tickets' ? 'No' : 'Yes',
    //             'date_started'   => $ticket->ticket_type == 'New Tickets' ? 'No Date' : $ticket->created_at->format('m/d/Y'),
    //             'completed'      => $ticket->ticket_status == 'Completed' ? 'Yes' : 'No',
    //             'user_comment'   => $ticket->user_comment ?? 'No Comment',
    //         ];
    //     }

    //     if ($request->inspection_sheet_id) {
    //         $inspection_sheet = InspectionSheet::with('ticket.asset', 'ticket.asset.organization', 'ticket.assigned_user')->where('inspection_order_number', $request->inspection_sheet_id)->first();

    //         $data = [
    //             'report_id'                   => $inspection_sheet->inspection_order_number,
    //             'inspection_sheet'            => $inspection_sheet->inspection_order_number,
    //             'organization'                => $inspection_sheet->ticket->asset->organization->name ?? 'N/A',
    //             'date_created'                => now()->format('m/d/Y H:i:s A'),
    //             'ticket_details'              => [
    //                 'make_and_model'    => $inspection_sheet->ticket->asset->product ?? 'N/A',
    //                 'serial_number'     => $inspection_sheet->ticket->asset->serial_number ?? 'N/A',
    //                 'fault_description' => $inspection_sheet->ticket->problem ?? 'N/A',
    //                 'ticket'            => $inspection_sheet->ticket->order_number ?? 'N/A',
    //             ],
    //             'contact'                     => $inspection_sheet->ticket->asset->brand ?? 'N/A',
    //             'status'                      => $inspection_sheet->status ?? 'N/A',
    //             'technician'                  => $inspection_sheet->ticket->assigned_user->name ?? 'Unassigned',
    //             'started'                     => $inspection_sheet->inspection_sheet_type == 'New Sheets' ? 'No' : 'Yes',
    //             'date_started'                => $inspection_sheet->inspection_sheet_type == 'New Sheets' ? 'No Date' : $inspection_sheet->created_at->format('m/d/Y'),
    //             'completed'                   => $inspection_sheet->status == 'Completed' ? 'Yes' : 'No',
    //             'support_agent_comment'       => $inspection_sheet->support_agent_comment ?? 'No Comment',
    //             'technician_comment'          => $inspection_sheet->technician_comment ?? 'No Comment',
    //             'location_employee_signature' => $inspection_sheet->location_employee_signature ?? 'No Comment',
    //         ];

    //     }

    //     if ($request->job_card_id) {
    //         $jobcard = JobCard::with(
    //             'supportAgent:id,name,email,role,address,phone,image,status',
    //             'ticket.user:id,name,email,role,address,phone,image,status',
    //             'ticket.asset.organization',
    //             'inspectionSheet.assigned:id,name,email,role,address,phone,image,status',
    //             'inspectionSheet.technician'
    //         )->where('job_card_order_number', $request->job_card_id)->first();

    //         $data = [
    //             'report_id'             => $jobcard->job_card_order_number,
    //             'job_card_id'           => $jobcard->job_card_order_number,
    //             'organization'          => $jobcard->ticket->asset->organization->name ?? 'N/A',
    //             'date_created'          => now()->format('m/d/Y H:i:s A'),
    //             'job_details'           => [
    //                 'make_and_model'    => $jobcard->ticket->asset->product ?? 'N/A',
    //                 'serial_number'     => $jobcard->ticket->asset->serial_number ?? 'N/A',
    //                 'fault_description' => $jobcard->ticket->problem ?? 'N/A',
    //                 'ticket'            => $jobcard->ticket->order_number ?? 'N/A',
    //             ],
    //             'contact'               => $jobcard->ticket->asset->brand ?? 'N/A',
    //             'status'                => $jobcard->job_status ?? 'N/A',
    //             'technician'            => $jobcard->inspectionSheet->technician->name ?? 'Unassigned',
    //             'started'               => $jobcard->job_card_type == 'New Cards' ? 'No' : 'Yes',
    //             'date_started'          => $jobcard->job_card_type == 'New Cards' ? 'No Date' : $jobcard->created_at->format('m/d/Y'),
    //             'completed'             => $jobcard->job_status == 'Completed' ? 'Yes' : 'No',
    //             'support_agent_comment' => $jobcard->support_agent_comment ?? 'No Comment',
    //             'technician_comment'    => $jobcard->technician_comment ?? 'No Comment',
    //         ];
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Report made successfully',
    //         'ticket_id'=>$request->ticket_id,
    //         'inspection_sheet_id'=>$request->inspection_sheet_id,
    //         'job_card_id'=>$request->job_card_id,
    //         'data'    => $data ?? 'No matching data found.',
    //     ]);
    // }


    public function makeReport(Request $request)
{
    try {
        $data = null;

        if ($request->ticket_id) {
            $ticket = Ticket::with(
                'user:id,name,email,address,role,phone,image,status',
                'asset.organization:id,name,email,address,role,phone,image,status',
                'assigned_user:id,name,email,address,role,phone,image,status'
            )->where('order_number', $request->ticket_id)->first();

            if (!$ticket) {
                throw new \Exception("Ticket not found");
            }

            $data = [
                'report_id'      => $ticket->order_number,
                'ticket_id'      => $ticket->order_number,
                'organization'   => optional(optional($ticket->asset)->organization)->name ?? 'N/A',
                'date_created'   => now()->format('m/d/Y H:i:s A'),
                'ticket_details' => [
                    'make_and_model'    => optional($ticket->asset)->product ?? 'N/A',
                    'serial_number'     => optional($ticket->asset)->serial_number ?? 'N/A',
                    'fault_description' => $ticket->problem ?? 'N/A',
                    'ticket'            => $ticket->order_number ?? 'N/A',
                ],
                'contact'        => optional($ticket->asset)->brand ?? 'N/A',
                'status'         => $ticket->ticket_status ?? 'N/A',
                'technician'     => optional($ticket->assigned_user)->name ?? 'Unassigned',
                'started'        => $ticket->ticket_type == 'New Tickets' ? 'No' : 'Yes',
                'date_started'   => $ticket->ticket_type == 'New Tickets' ? 'No Date' : $ticket->created_at->format('m/d/Y'),
                'completed'      => $ticket->ticket_status == 'Completed' ? 'Yes' : 'No',
                'user_comment'   => $ticket->user_comment ?? 'No Comment',
            ];
        }

        if ($request->inspection_sheet_id) {
            $inspection_sheet = InspectionSheet::with(
                'ticket.asset',
                'ticket.asset.organization',
                'ticket.assigned_user'
            )->where('inspection_order_number', $request->inspection_sheet_id)->first();

            if (!$inspection_sheet) {
                throw new \Exception("Inspection Sheet not found");
            }

            $data = [
                'report_id'                   => $inspection_sheet->inspection_order_number,
                'inspection_sheet'            => $inspection_sheet->inspection_order_number,
                'organization'                => optional(optional(optional($inspection_sheet->ticket)->asset)->organization)->name ?? 'N/A',
                'date_created'                => now()->format('m/d/Y H:i:s A'),
                'ticket_details'              => [
                    'make_and_model'    => optional(optional($inspection_sheet->ticket)->asset)->product ?? 'N/A',
                    'serial_number'     => optional(optional($inspection_sheet->ticket)->asset)->serial_number ?? 'N/A',
                    'fault_description' => optional($inspection_sheet->ticket)->problem ?? 'N/A',
                    'ticket'            => optional($inspection_sheet->ticket)->order_number ?? 'N/A',
                ],
                'contact'                     => optional(optional($inspection_sheet->ticket)->asset)->brand ?? 'N/A',
                'status'                      => $inspection_sheet->status ?? 'N/A',
                'technician'                  => optional(optional($inspection_sheet->ticket)->assigned_user)->name ?? 'Unassigned',
                'started'                     => $inspection_sheet->inspection_sheet_type == 'New Sheets' ? 'No' : 'Yes',
                'date_started'                => $inspection_sheet->inspection_sheet_type == 'New Sheets' ? 'No Date' : $inspection_sheet->created_at->format('m/d/Y'),
                'completed'                   => $inspection_sheet->status == 'Completed' ? 'Yes' : 'No',
                'support_agent_comment'       => $inspection_sheet->support_agent_comment ?? 'No Comment',
                'technician_comment'          => $inspection_sheet->technician_comment ?? 'No Comment',
                'location_employee_signature' => $inspection_sheet->location_employee_signature ?? 'No Comment',
            ];
        }

        if ($request->job_card_id) {
            $jobcard = JobCard::with(
                'supportAgent:id,name,email,role,address,phone,image,status',
                'ticket.user:id,name,email,role,address,phone,image,status',
                'ticket.asset.organization',
                'inspectionSheet.assigned:id,name,email,role,address,phone,image,status',
                'inspectionSheet.technician'
            )->where('job_card_order_number', $request->job_card_id)->first();

            if (!$jobcard) {
                throw new \Exception("Job Card not found");
            }

            $data = [
                'report_id'             => $jobcard->job_card_order_number,
                'job_card_id'           => $jobcard->job_card_order_number,
                'organization'          => optional(optional($jobcard->ticket->asset)->organization)->name ?? 'N/A',
                'date_created'          => now()->format('m/d/Y H:i:s A'),
                'job_details'           => [
                    'make_and_model'    => optional($jobcard->ticket->asset)->product ?? 'N/A',
                    'serial_number'     => optional($jobcard->ticket->asset)->serial_number ?? 'N/A',
                    'fault_description' => optional($jobcard->ticket)->problem ?? 'N/A',
                    'ticket'            => optional($jobcard->ticket)->order_number ?? 'N/A',
                ],
                'contact'               => optional($jobcard->ticket->asset)->brand ?? 'N/A',
                'status'                => $jobcard->job_status ?? 'N/A',
                'technician'            => optional(optional($jobcard->inspectionSheet)->technician)->name ?? 'Unassigned',
                'started'               => $jobcard->job_card_type == 'New Cards' ? 'No' : 'Yes',
                'date_started'          => $jobcard->job_card_type == 'New Cards' ? 'No Date' : $jobcard->created_at->format('m/d/Y'),
                'completed'             => $jobcard->job_status == 'Completed' ? 'Yes' : 'No',
                'support_agent_comment' => $jobcard->support_agent_comment ?? 'No Comment',
                'technician_comment'    => $jobcard->technician_comment ?? 'No Comment',
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'Report made successfully',
            'ticket_id' => $request->ticket_id,
            'inspection_sheet_id' => $request->inspection_sheet_id,
            'job_card_id' => $request->job_card_id,
            'data'    => $data ?? 'No matching data found.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Error generating report: ' . $e->getMessage(),
            'data'    => null,
        ], 500);
    }
}


}
