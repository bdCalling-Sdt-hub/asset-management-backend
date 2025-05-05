<?php
namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Report;
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
            'job_card_id'         => 'nullable|string|exists:job_cards,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        if (! $request->job_card_id) {
            return response()->json(['status' => false, 'message' => 'At least one ID (Job Card) is required'], 400);
        }

        $reportType = $request->job_card_id ? 'job_card' : null;

        $report = Report::create([
            'user_id'             => Auth::id(), // Get authenticated user ID
            'job_card_id'         => $request->job_card_id,
            'report_type'         => $reportType,
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
        if (!$reports) {
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
        if (!$reports) {
            return response()->json(['status' => true, 'message' => 'No reports found'], 200);
        }


        return response()->json([
            'status'  => true,
            'message' => 'Reports retrieved successfully',
            'reports' => $reports,
        ]);
    }

}
