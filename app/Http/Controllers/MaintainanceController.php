<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Maintainance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MaintainanceController extends Controller
{
    public function TechnicianGet()
    {
        $technicians = User::where('role', 'technician')->select('id', 'name', 'image', 'email', 'role')->get();
        return response()->json([
            'status'  => true,
            'message' => 'Technician retrieve successfully.',
            'data'    => $technicians,
        ]);
    }

    public function assetGet()
    {
        $assets = Asset::select('id', 'product', 'brand','location')->get();
        return response()->json([
            'status'  => true,
            'message' => 'Asset retrieve successfully.',
            'data'    => $assets,
        ]);
    }

    public function setReminder(Request $request)
    {
        $maintainance = Maintainance::create([
            'user_id'           => Auth::user()->id,
            'asset_id'          => $request->asset_id,
            'technician_id'     => $request->technician_id,
            'last_maintainance' => $request->last_maintainance,
            'next_schedule'     => $request->next_schedule,
            'status'            => $request->status,
            'reminder_category' => $request->reminder_category,
            'location' => $request->location,
        ]);
        return response()->json([
            'status'  => true,
            'message' => 'Remainder add successfully.',
            'data'    => $maintainance,
        ]);
    }
    public function getReminder()
    {
        $reminder = Maintainance::with(['asset'])->where('user_id', Auth::user()->id)->where('status', 'RemindMeLetter')->first();
        if ($reminder) {
            return response()->json([
                'status'  => true,
                'message' => 'You have a new reminder',
                'data'    => $reminder,
            ]);
        }
        return response()->json([
            'status'  => false,
            'message' => "You don't have any reminder right now.",
            'data'    => $reminder,
        ]);
    }

    public function maintainanceGet(Request $request)
    {
        $maintainances = Maintainance::with(['asset:id,product', 'technician:id,name'])->latest('id');

        if ($request->search) {
            $maintainances = $maintainances->whereHas('asset', function ($q) use ($request) {
                $q->where('product', 'LIKE', '%' . $request->search . '%');
            });
        }

        $maintainances = $maintainances->paginate($request->per_page ?? 10);

        $filtered = $maintainances->getCollection()->transform(function ($maintainance) use ($request) {
            $last_maintainance_date = Carbon::parse($maintainance->last_maintainance_date);
            $next_schedule          = Carbon::parse($maintainance->next_schedule);
            $diffInYears            = $last_maintainance_date->diffInYears($next_schedule);
            $diffInMonths           = $last_maintainance_date->diffInMonths($next_schedule);

            if ($diffInYears >= 1) {
                $maintainance_type = 'Urgent';
            } elseif ($diffInMonths > 6 && $diffInYears < 1) {
                $maintainance_type = 'Medium';
            } else {
                $maintainance_type = 'Low';
            }

            $maintainance->maintainance_type = $maintainance_type;

            return [
                'id'                     => $maintainance->id,
                'maintainance_item'      => $maintainance->asset->product ?? null,
                'checked_list'           => $maintainance->checked,
                'last_maintainance_date' => $maintainance->last_maintainance,
                'technician'             => $maintainance->technician->name ?? null,
                'next_schedule'          => $maintainance->next_schedule,
                'maintainance_type'      => $maintainance_type,
                'location'      => $maintainance->location,
            ];
        });

        if ($request->sort) {
            $filtered = $filtered->filter(function ($item) use ($request) {
                return $item['maintainance_type'] === $request->sort;
            })->values();
        }

        $maintainances->setCollection($filtered);

        return response()->json([
            'status'  => true,
            'message' => 'Data retrieved successfully.',
            'data'    => $maintainances,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'checked' => 'required',
        ]);
        $maintainance = Maintainance::findOrFail($id);
        $maintainance->update([
            'checked' => $request->checked,
            'status'  => $request->status,
        ]);
        return response()->json([
            'status'  => true,
            'message' => 'Data update successfully.',
            'data'    => $maintainance,
        ]);
    }
}
