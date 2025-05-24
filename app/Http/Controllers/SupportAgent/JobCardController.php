<?php
namespace App\Http\Controllers\SupportAgent;

use App\Http\Controllers\Controller;
use App\Models\InspectionSheet;
use App\Models\JobCard;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\JobCardNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobCardController extends Controller
{
    public function createJobCard(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'ticket_id'                   => 'nullable|string|exists:tickets,id',
                'inspection_sheet_id'         => 'required|string|exists:inspection_sheets,id',
                'job_card_type'               => 'nullable|string',
                'support_agent_comment'       => 'required|string',
                'technician_comment'          => 'nullable|string',
                'location_employee_signature' => 'nullable|string',
                'job_status'                  => 'nullable|string',
            ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }
        $job_card = JobCard::create([
            'support_agent_id'            => Auth::user()->id,
            'ticket_id'                   => $request->ticket_id,
            'inspection_sheet_id'         => $request->inspection_sheet_id,
            'job_card_type'               => $request->job_card_type ?? 'New Cards',
            'support_agent_comment'       => $request->support_agent_comment,
            'technician_comment'          => $request->technician_comment,
            'location_employee_signature' => $request->location_employee_signature,
            'job_status'                  => $request->job_status ?? 'New',
            'job_card_order_number'       => rand(10000000, 99999999),
        ]);
        $job_card->load(
            'supportAgent:id,name',
            'inspectionSheet:id,ticket_id,technician_id',
            'inspectionSheet.technician:id,name',
            'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
            'inspectionSheet.ticket.asset:id,product,brand,serial_number',
            'inspectionSheet.ticket.user:id,name,address,phone'

        );

        // Notify relevant users
        $usersToNotify = User::whereIn('role', ['super_admin', 'organization', 'third_party', 'location_employee', 'technician'])->get();
        foreach ($usersToNotify as $user) {
            $user->notify(new JobCardNotification($job_card));
        }

        $job_card->save();

        return response()->json(['status' => true, 'message' => 'Job Card Create Successfully', 'data' => $job_card], 201);

    }
    // public function updateJobCard(Request $request, $id)
    // {

    //     $job_card = JobCard::with('supportAgent:id,name',
    //         'inspectionSheet:id,ticket_id,technician_id',
    //         'inspectionSheet.technician:id,name,image',
    //         'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
    //         'inspectionSheet.ticket.asset:id,product,brand,serial_number',
    //         'inspectionSheet.ticket.user:id,name,address,phone')->findOrFail($id);

    //     if (! $job_card) {
    //         return response()->json(['status' => false, 'message' => 'Job Card Not Found'], 422);
    //     }
    //     $validator = Validator::make($request->all(), [
    //         'job_card_type'               => 'nullable|string',
    //         'support_agent_comment'       => 'nullable|string',
    //         'technician_comment'          => 'nullable|string',
    //         'location_employee_signature' => 'nullable|string',
    //         'job_status'                  => 'nullable|string|in:New,Assigned,In Progress,On hold,Cancel,To be allocated,Awaiting courier
    //                                                             ,Collected by courier,Parts required,Picking,To be invoiced,Invoiced,Completed',
    //     ]);
    //     $validatedData = $validator->validated();

    //     if (isset($validatedData['job_status'])) {
    //         $validatedData['job_card_type'] = ($validatedData['job_status'] === 'Completed') ? 'Past Cards' : 'Open Cards';
    //     } elseif ($job_card->job_status === 'Completed') {
    //         $validatedData['job_card_type'] = 'Past Cards';
    //     } else {
    //         $validatedData['job_card_type'] = 'Open Cards';
    //     }
    //     $job_card->update($validatedData);

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Inspection Sheet Update Successfully',
    //         'data'    => $job_card,
    //     ]);

    // }

    public function updateJobCard(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'images'   => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:10240',
            'videos'   => 'sometimes|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ]);
        }
        $job_card = JobCard::with(
            'supportAgent:id,name',
            'inspectionSheet:id,ticket_id,technician_id',
            'inspectionSheet.technician:id,name,image',
            'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
            'inspectionSheet.ticket.asset:id,product,brand,serial_number',
            'inspectionSheet.ticket.user:id,name,address,phone'
        )->findOrFail($id);

        if (! $job_card) {
            return response()->json(['status' => false, 'message' => 'Job Card Not Found'], 422);
        }

        if ($request->has('support_agent_comment')) {
            $job_card->support_agent_comment = $request->support_agent_comment;
        }

        if ($request->has('technician_comment')) {
            $job_card->technician_comment = $request->technician_comment;
        }

        if ($request->has('location_employee_signature')) {
            $job_card->location_employee_signature = $request->location_employee_signature;
        }

        if ($request->has('job_status')) {
            $job_card->job_status    = $request->job_status;
            $job_card->job_card_type = ($request->job_status === 'Completed') ? 'Past Cards' : 'Open Cards';
        } else {
            $job_card->job_card_type = ($job_card->job_status === 'Completed') ? 'Past Cards' : 'Open Cards';
        }

        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $ImageName = time() . uniqid() . $image->getClientOriginalName();
                $image->move(public_path('uploads/job_card_images'), $ImageName);
                $newImages[] = $ImageName;
            }

            $job_card->image = $newImages;
        }

        // Handle video update or add
        if ($request->hasFile('videos')) {

            // Upload new videos
            $newVideos = [];
            foreach ($request->file('videos') as $video) {
                $VideoName = time() . uniqid() . $video->getClientOriginalName();
                $video->move(public_path('uploads/job_card_videos'), $VideoName);
                $newVideos[] = $VideoName;
            }

            $job_card->video = $newVideos;
        }

        $job_card->save();

        return response()->json([
            'status'  => true,
            'message' => 'Job card Update Successfully',
            'data'    => $job_card,
        ]);
    }

    public function deleteJobCard($id)
    {
        $job_card = JobCard::find($id);

        if (! $job_card) {
            return response()->json(['status' => 'error', 'message' => 'Job Card not found.'], 422);
        }

        $job_card->delete();

        return response()->json([
            'status' => true, 'message' => 'Job Card deleted successfully'], 200);
    }

    public function JobCardList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search');
        $filter  = $request->input('filter');
        $type    = $request->input('type');

        if (Auth::user()->role == 'technician') {
            $assign_ticket_ids = InspectionSheet::where('technician_id', Auth::user()->id)
                ->pluck('ticket_id')
                ->unique();
            $ticketListIds = Ticket::whereIn('id', $assign_ticket_ids)->pluck('id');
            $cardList      = JobCard::with(
                'supportAgent:id,name',
                'inspectionSheet:id,ticket_id,support_agent_id,technician_id',
                'inspectionSheet.assigned:id,name',
                'inspectionSheet.technician:id,name,image',
                'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
                'inspectionSheet.ticket.asset:id,product,brand,serial_number',
                'inspectionSheet.ticket.user:id,name,address,phone')->whereIn('ticket_id', $ticketListIds);
        } else {
            $cardList = JobCard::with('supportAgent:id,name',
                'inspectionSheet:id,ticket_id,support_agent_id,technician_id',
                'inspectionSheet.assigned:id,name',
                'inspectionSheet.technician:id,name,image',
                'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
                'inspectionSheet.ticket.asset:id,product,brand,serial_number',
                'inspectionSheet.ticket.user:id,name,address,phone');
        }

        if ($type) {
            $cardList = $cardList->where('job_card_type', $type);
        }
        if ($search) {
            $cardList = $cardList->where(function ($query) use ($search) {
                $query->where('job_card_order_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('inspectionSheet.ticket.asset', function ($q) use ($search) {
                        $q->where('product', 'LIKE', '%' . $search . '%')
                            ->orWhere('serial_number', 'LIKE', '%' . $search . '%');
                    });
            });
        }

        if (! empty($filter)) {
            $cardList = $cardList->where('job_status', $filter);
        }
        $cardList = $cardList->paginate($perPage);

        return response()->json(['status' => true, 'data' => $cardList], 200);
    }

    public function detailsJobCard(Request $request, $id)
    {
        $card_details = JobCard::with('supportAgent:id,name',
            'inspectionSheet:id,ticket_id,support_agent_id,technician_id',
            'inspectionSheet.assigned:id,name',
            'inspectionSheet.technician:id,name,image',
            'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
            'inspectionSheet.ticket.asset:id,product,brand,serial_number',
            'inspectionSheet.ticket.user:id,name,address,phone')->find($id);
        if (! $card_details) {
            return response()->json(['status' => false, 'message' => 'Job Card Not Found'], 422);
        }

        return response()->json([
            'status' => true,
            'data'   => $card_details,
        ]);

    }

}
