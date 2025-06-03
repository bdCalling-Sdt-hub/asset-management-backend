<?php
namespace App\Http\Controllers\SupportAgent;

use App\Http\Controllers\Controller;
use App\Models\InspectionSheet;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\InspectionSheetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InspectionSheetController extends Controller
{
    // create inspection sheet
    public function createInspectionSheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id'                   => 'required|string|exists:tickets,id',
            'technician_id'               => 'required|string|exists:users,id',
            'inspection_sheet_type'       => 'nullable|string',
            'support_agent_comment'       => 'nullable|string',
            'technician_comment'          => 'nullable|string',
            'location_employee_signature' => 'nullable|string',
            'image'                       => 'nullable|',
            'video'                       => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }

        // Image upload
        $newImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . uniqid() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/sheet_images'), $imageName);
                $newImages[] = $imageName;
            }
        }

        // Video upload
        $newVideos = [];
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $video) {
                $videoName = time() . uniqid() . '_' . $video->getClientOriginalName();
                $video->move(public_path('uploads/sheet_videos'), $videoName);
                $newVideos[] = $videoName;
            }
        }

        $inspectionSheet = InspectionSheet::create([
            'support_agent_id'            => auth()->id(),
            'ticket_id'                   => $request->ticket_id,
            'technician_id'               => $request->technician_id,
            'inspection_sheet_type'       => $request->inspection_sheet_type ?? 'New Sheets',
            'support_agent_comment'       => $request->support_agent_comment,
            'technician_comment'          => $request->technician_comment,
            'location_employee_signature' => $request->location_employee_signature ?? null,
            'image'                       => json_encode($newImages),
            'video'                       => json_encode($newVideos),
            'status'                      => $request->status ?? 'New',
            'inspection_order_number'     => rand(10000000, 99999999),
        ]);
        $ticket = Ticket::findOrFail($request->ticket_id);
        $ticket->update([
            'assigned_user_id' => auth()->id(),
            'ticket_type'=>'Open Tickets',
            'ticket_status'=>'Assigned'
        ]);

        // Eager load the related user and asset
        $inspectionSheet->load('assigned:id,name', 'ticket:id,problem,asset_id,user_id', 'ticket.user:id,name,address', 'ticket.asset:id,product,brand,serial_number', 'technician:id,name');
        // Notify relevant users
        $usersToNotify = User::whereIn('role', ['super_admin', 'organization', 'third_party', 'location_employee', 'technician'])->get();
        foreach ($usersToNotify as $user) {
            $user->notify(new InspectionSheetNotification($inspectionSheet));
        }
        return response()->json(['status' => true, 'message' => 'Inspection Sheet Created Successfully', 'data' => $inspectionSheet]);
    }
    public function updateInspectionSheet(Request $request, $id)
    {
        $inspection_sheet = InspectionSheet::with('assigned:id,name', 'ticket:id,problem,asset_id,user_id', 'ticket.user:id,name,address,phone', 'ticket.asset:id,product,brand,serial_number', 'technician:id,name')->findOrFail($id);

        if (! $inspection_sheet) {
            return response()->json(['status' => false, 'message' => 'Inspection Sheet Not Found'], 200);
        }

        $validator = Validator::make($request->all(), [
            'inspection_sheet_type'       => 'nullable|string',
            'support_agent_comment'       => 'nullable|string',
            'technician_comment'          => 'nullable|string',
            'location_employee_signature' => 'nullable|string',
            'image'                       => 'nullable|string',
            'video'                       => 'nullable',
            'status'                      => 'nullable|string|in:New,Arrived in Location,Contract with user,View the problem,Solve the problem,Completed',
        ]);

        $validatedData = $validator->validated();

        if (isset($validatedData['status'])) {
            $validatedData['inspection_sheet_type'] = ($validatedData['status'] === 'Completed') ? 'Past Sheets' : 'Open Sheets';
        } elseif ($inspection_sheet->status === 'Completed') {
            $validatedData['inspection_sheet_type'] = 'Past Sheets';
        } else {
            $validatedData['inspection_sheet_type'] = 'Open Sheets';
        }

        // Handle image update or add
        if ($request->hasFile('images')) {
            $existingImages = $inspection_sheet->image;

            // Delete old images
            if ($existingImages) {
                foreach ($existingImages as $image) {
                    $relativePath = parse_url($image, PHP_URL_PATH);
                    $relativePath = ltrim($relativePath, '/');
                    if (file_exists(public_path($relativePath))) {
                        unlink(public_path($relativePath));
                    }
                }
            }

            // Upload new images
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $ImageName = time() . uniqid() . $image->getClientOriginalName();
                $image->move(public_path('uploads/sheet_images'), $ImageName);
                $newImages[] = $ImageName;
            }

            $validatedData['image'] = json_encode($newImages);
        }

        // Handle video update or add
        if ($request->hasFile('videos')) {
            $existingVideos = $inspection_sheet->video;

            // Delete old videos
            if ($existingVideos) {
                foreach ($existingVideos as $video) {
                    $relativePath = parse_url($video, PHP_URL_PATH);
                    $relativePath = ltrim($relativePath, '/');
                    if (file_exists(public_path($relativePath))) {
                        unlink(public_path($relativePath));
                    }
                }
            }

            // Upload new videos
            $newVideos = [];
            foreach ($request->file('videos') as $video) {
                $VideoName = time() . uniqid() . $video->getClientOriginalName();
                $video->move(public_path('uploads/sheet_videos'), $VideoName);
                $newVideos[] = $VideoName;
            }

            $validatedData['video'] = json_encode($newVideos);
        }

        $inspection_sheet->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'Inspection Sheet Update Successfully',
            'data'    => $inspection_sheet,
        ]);
    }
    //delete inspection sheet
    public function deleteInspectionSheet($id)
    {
        $inspection_sheet = InspectionSheet::find($id);

        if (! $inspection_sheet) {
            return response()->json(['status' => false, 'message' => 'Inspection sheet not found.'], 200);
        }

        // Delete associated images
        $existingImages = $inspection_sheet->image;
        if ($existingImages) {
            foreach ($existingImages as $image) {
                $relativePath = parse_url($image, PHP_URL_PATH);
                $relativePath = ltrim($relativePath, '/');
                if (file_exists(public_path($relativePath))) {
                    unlink(public_path($relativePath));
                }
            }
        }

        // Delete associated videos
        $existingVideos = $inspection_sheet->video;
        if ($existingVideos) {
            foreach ($existingVideos as $video) {
                $relativePath = parse_url($video, PHP_URL_PATH);
                $relativePath = ltrim($relativePath, '/');
                if (file_exists(public_path($relativePath))) {
                    unlink(public_path($relativePath));
                }
            }
        }

        $inspection_sheet->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Inspection Sheet deleted successfully',
        ], 200);
    }
    //inspection sheet list
    public function InspectionSheetList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search');
        $filter  = $request->input('filter');
        $type    = $request->input('type');

        $inspectionList = InspectionSheet::with('assigned:id,name', 'ticket:id,asset_id,user_id,order_number,problem',
            'ticket.asset:id,product,brand,serial_number', 'ticket.user:id,name,address,phone', 'technician:id,name,image');
        if (Auth::user()->role == 'support_agent') {
            $inspectionList = $inspectionList->where('support_agent_id', Auth::user()->id);
        } elseif (Auth::user()->role == 'technician') {
            $inspectionList = $inspectionList->where('technician_id', Auth::user()->id);
        }
        if ($type) {
            $inspectionList = $inspectionList->where('inspection_sheet_type', $type);
        }

   if ($search) {
    $inspectionList = $inspectionList->where(function ($query) use ($search) {
        $query->where('inspection_order_number', 'LIKE', '%' . $search . '%')
            ->orWhereHas('ticket.asset', function ($q) use ($search) {
                $q->where('product', 'LIKE', '%' . $search . '%')
                    ->orWhere('serial_number', 'LIKE', '%' . $search . '%');
            });
    });
}


        if ($filter) {
            $inspectionList = $inspectionList->where('status', 'LIKE', '%' . $filter . '%');
        }
        $inspectionList = $inspectionList->latest('id')->paginate($perPage);

        return response()->json(['status' => true, 'data' => $inspectionList], 200);

    }
    public function InspectionSheetDetails(Request $request)
    {
        if ($request->ticket_id) {
            $sheet_details = InspectionSheet::with('assigned:id,name', 'ticket:id,asset_id,user_id,problem',
                'ticket.asset:id,product,brand,serial_number', 'ticket.user:id,name,address,phone', 'technician:id,name,image')->where('ticket_id', $request->ticket_id)->first();

        } elseif ($request->inspection_id) {
            $sheet_details = InspectionSheet::with('assigned:id,name', 'ticket:id,asset_id,user_id,problem',
                'ticket.asset:id,product,brand,serial_number,organization_id', 'ticket.user:id,name,address,phone', 'technician:id,name,image','ticket.asset.organization:id,name,email,role,image')->find($request->inspection_id);
        }

        if (! $sheet_details) {
            return response()->json(['status' => true, 'message' => 'Inspection Sheet Not Found'], 200);
        }

        return response()->json([
            'status' => true,
            'data'   => $sheet_details,
        ]);
    }

}
