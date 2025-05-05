<?php
namespace App\Http\Controllers\SupportAgent;

use App\Models\JobCard;
use App\Notifications\JobCardNotification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
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
    public function updateJobCard(Request $request, $id)
    {
        $job_card = JobCard::with('supportAgent:id,name',
            'inspectionSheet:id,ticket_id,technician_id',
            'inspectionSheet.technician:id,name,image',
            'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
            'inspectionSheet.ticket.asset:id,product,brand,serial_number',
            'inspectionSheet.ticket.user:id,name,address,phone')->findOrFail($id);

        if (! $job_card) {
            return response()->json(['status' => false, 'message' => 'Job Card Not Found'], 422);
        }
        $validator = Validator::make($request->all(), [
            'job_card_type'               => 'nullable|string',
            'support_agent_comment'       => 'nullable|string',
            'technician_comment'          => 'nullable|string',
            'location_employee_signature' => 'nullable|string',
            'job_status'                  => 'nullable|string|in:New,Assigned,In Progress,On hold,Cancel,To be allocated,Awaiting courier
                                                                ,Collected by courier,Parts required,Picking,To be invoiced,Invoiced,Completed',
        ]);
        $validatedData = $validator->validated();

        if (isset($validatedData['job_status'])) {
            $validatedData['job_card_type'] = ($validatedData['job_status'] === 'Completed') ? 'Past Cards' : 'Open Cards';
        } elseif ($job_card->job_status === 'Completed') {
            $validatedData['job_card_type'] = 'Past Cards';
        } else {
            $validatedData['job_card_type'] = 'Open Cards';
        }
        $job_card->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'Inspection Sheet Update Successfully',
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

        $cardList = JobCard::with('supportAgent:id,name',
            'inspectionSheet:id,ticket_id,support_agent_id,technician_id',
            'inspectionSheet.assigned:id,name',
            'inspectionSheet.technician:id,name,image',
            'inspectionSheet.ticket:id,asset_id,problem,order_number,cost,user_id',
            'inspectionSheet.ticket.asset:id,product,brand,serial_number',
            'inspectionSheet.ticket.user:id,name,address,phone');

        if ($search) {
            $cardList = $cardList->where('inspection_sheet_type', $search);
        }
        if (! empty($filter)) {
            $cardList = $cardList->where('status', $filter);
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
            'data'   =>$card_details
        ]);

    }

    //notification part
     //get all notification
     public function notifications(Request $request)
     {
         $perPage = $request->query('per_page', 10);
         $user = Auth::user();

         if (!$user) {
             return response()->json(['status' => false, 'message' => 'Authorization User Not Found'], 401);
         }

         $notifications = $user->notifications()->paginate($perPage);
         $unreadCount = $user->unreadNotifications()->count();

         return response()->json([
             'status' => 'success',
             'unread_notifications' => $unreadCount,
             'notifications' => $notifications,
         ], 200);
     }
     //read one notification
     public function notificationMark($notificationId)
     {
         $user = Auth::user();

         if (!$user) {
             return response()->json(['status' => false,'message'=>'Authorization User Not Found'], 401);
         }

         $notification = $user->notifications()->find($notificationId);

         if (!$notification) {
             return response()->json(['message' => 'Notification not found.'], 401);
         }

         if (!$notification->read_at) {
             $notification->markAsRead();
         }

         return response()->json([
             'status' => 'success',
             'message' => 'Notification marked as read.'], 200);
     }
     //read all notification
     public function allNotificationMark(Request $request)
     {
         $user = Auth::user();

         if (!$user) {
             return response()->json(['status' => false,'message'=>'Authorization User Not Found'], 401);
         }

         $notifications = $user->unreadNotifications;

         if ($notifications->isEmpty()) {
             return response()->json(['status'=>'true','message' => 'No unread notifications found.'], 200);
         }

         $notifications->markAsRead();

         return response()->json([
             'status' => 'success',
             'message' => 'All notifications marked as read.',
         ], 200);
     }

}
