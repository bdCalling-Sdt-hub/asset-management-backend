<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Maintainance;
use App\Models\MaintainanceCheck;
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
        $assets = Asset::select('id', 'product', 'brand', 'location')->get();
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
            'location'          => $request->location,
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
        $type          = $request->maintainance_type;
        $maintainances = Maintainance::with(['asset:id,product', 'technician:id,name']);

        if ($request->search) {
            $maintainances = $maintainances->whereHas('asset', function ($q) use ($request) {
                $q->where('product', 'LIKE', '%' . $request->search . '%');
            });
        }

        $maintainances = $maintainances->paginate($request->per_page ?? 10);

        $filtered = $maintainances->getCollection()->transform(function ($maintainance) use ($request, $type) {
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
            $request_data                    = new Request([
                'maintainance_id'   => $maintainance->id,
                'maintainance_type' => $type,
            ]);
            $checked_maintainance = $this->getCheckedMaintainance($request_data);
            $dataArray            = $checked_maintainance->getData(true);

            $checked_data = $dataArray['data'];
            return [
                'id'                     => $maintainance->id,
                'maintainance_item'      => $maintainance->asset->product ?? null,
                'last_maintainance_date' => $maintainance->last_maintainance,
                'technician'             => $maintainance->technician->name ?? null,
                'next_schedule'          => $maintainance->next_schedule,
                'maintainance_type'      => $maintainance_type,
                'checked_maintainance'   => $checked_data,
                'location'               => $maintainance->location,
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
        if ($validation->fails()) {
            return response()->json(['error', $validation->errors()]);
        }
        $maintainance = Maintainance::findOrFail($id);
        $maintainance->update([
            'status' => $request->status,
        ]);
        return response()->json([
            'status'  => true,
            'message' => 'Data update successfully.',
            'data'    => $maintainance,
        ]);
    }

    public function toggleMaintainanceDay(Request $request)
    {
        $request->validate([
            'maintainance_id'   => 'required|exists:maintainances,id',
            'maintainance_type' => 'required|in:weekly,monthly,yearly',
            'day'               => 'nullable|string',
            'month'             => 'nullable|string',
            'year'              => 'nullable|integer|min:2000|max:2100',
        ]);

        $maintainanceId = $request->maintainance_id;
        $type           = $request->maintainance_type;
        $dayName        = $request->day;
        $currentYear    = now()->year; // Always use current year

        $query = MaintainanceCheck::where('maintainance_id', $maintainanceId)
            ->where('maintainance_type', $type);

        if ($type === 'weekly') {
            $startOfWeek = now()->startOfWeek()->toDateString();
            $endOfWeek   = now()->endOfWeek()->toDateString();

            $existing = $query->where('week', $dayName)
                ->whereYear('created_at', $currentYear)
                ->whereDate('period_start', $startOfWeek)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName removed from this week’s maintenance.",
                    'action'  => 'unchecked',
                ]);
            } else {
                $check = MaintainanceCheck::create([
                    'maintainance_id'   => $maintainanceId,
                    'maintainance_type' => $type,
                    'week'              => $dayName,
                    'period_start'      => $startOfWeek,
                    'period_end'        => $endOfWeek,
                ]);
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName added to this week’s maintenance.",
                    'action'  => 'checked',
                    'data'    => $check,
                ]);
            }

        } elseif ($type === 'monthly') {
            $monthName    = $request->month ?? now()->format('F');
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth   = now()->endOfMonth()->toDateString();

            $existing = $query
                ->where('month', $monthName)
                ->whereYear('created_at', $currentYear)
                ->whereDate('period_start', $startOfMonth)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName removed from this month’s maintenance.",
                    'action'  => 'unchecked',
                ]);
            } else {
                $check = MaintainanceCheck::create([
                    'maintainance_id'   => $maintainanceId,
                    'maintainance_type' => $type,
                    'day'               => $dayName,
                    'month'             => $monthName,
                    'period_start'      => $startOfMonth,
                    'period_end'        => $endOfMonth,
                ]);
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName added to this month’s maintenance.",
                    'action'  => 'checked',
                    'data'    => $check,
                ]);
            }

        } elseif ($type === 'yearly') {
            $startOfYear = now()->startOfYear()->toDateString();
            $endOfYear   = now()->endOfYear()->toDateString();

            $existing = $query
                ->where('year', $request->year)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName removed from this year’s maintenance.",
                    'action'  => 'unchecked',
                ]);
            } else {
                $check = MaintainanceCheck::create([
                    'maintainance_id'   => $maintainanceId,
                    'maintainance_type' => $type,
                    'day'               => $dayName,
                    'year'              =>  $request->year,
                    'period_start'      => $startOfYear,
                    'period_end'        => $endOfYear,
                ]);
                return response()->json([
                    'status'  => true,
                    'message' => "$dayName added to this year’s maintenance.",
                    'action'  => 'checked',
                    'data'    => $check,
                ]);
            }
        }

        return response()->json([
            'status'  => false,
            'message' => 'Invalid maintenance type or data.',
        ], 400);
    }

    // private function getCheckedMaintainance(Request $request)
    // {
    //     $startOfMonth      = now()->startOfMonth();
    //     $maintainanace_day = MaintainanceCheck::where('maintainance_id', $request->maintainance_id);

    //     if ($request->maintainance_type === 'weekly') {
    //         $startOfWeek = now()->startOfWeek();
    //         $endOfWeek   = now()->endOfWeek();

    //         $maintainanace_day = $maintainanace_day
    //             ->where('maintainance_type', 'weekly')
    //             ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
    //             ->pluck('week');
    //     } elseif ($request->maintainance_type == 'monthly') {
    //         $maintainanace_day = $maintainanace_day
    //             ->where('maintainance_type', 'monthly')
    //             ->whereYear('created_at', now())
    //             ->pluck('month');
    //     } elseif ($request->maintainance_type == 'yearly') {
    //         $maintainanace_day = $maintainanace_day
    //             ->where('maintainance_type', 'yearly')
    //             ->pluck('year');
    //     } else {
    //         $maintainanace_day = collect();
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Maintainace day retreived successfull',
    //         'data'    => $maintainanace_day,
    //     ]);
    // }

    private function getCheckedMaintainance(Request $request)
    {
        $maintainanceCheck = MaintainanceCheck::where('maintainance_id', $request->maintainance_id);

        if ($request->maintainance_type === 'weekly') {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek   = now()->endOfWeek();

            $maintainance_days = $maintainanceCheck
                ->where('maintainance_type', 'weekly')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->pluck('week')
                ->toArray();

            $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            $daysStatus = [];
            foreach ($weekDays as $day) {
                $daysStatus[$day] = in_array($day, $maintainance_days);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Weekly maintenance days retrieved successfully',
                'data'    => [
                    'maintainance_days' => $maintainance_days,
                    'days'              => $daysStatus,
                ],
            ]);

        } elseif ($request->maintainance_type === 'monthly') {

            $maintainance_days = $maintainanceCheck
                ->where('maintainance_type', 'monthly')
                ->whereYear('created_at', now())
                ->pluck('month')
                ->toArray();

            $months = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
            ];

            $monthsStatus = [];
            foreach ($months as $month) {
                $monthsStatus[$month] = in_array($month, $maintainance_days);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Monthly maintenance months retrieved successfully',
                'data'    => [
                    'maintainance_days' => $maintainance_days,
                    'months'            => $monthsStatus,
                ],
            ]);

        } elseif ($request->maintainance_type === 'yearly') {
            $maintainance_days = $maintainanceCheck
                ->where('maintainance_type', 'yearly')
                ->pluck('year')
                ->toArray();

            $currentYear = date('Y');
            $yearRange   = range($currentYear - 2, $currentYear + 4);

            $yearsStatus = [];
            foreach ($yearRange as $year) {
                $yearsStatus[$year] = in_array($year, $maintainance_days);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Yearly maintenance years retrieved successfully',
                'data'    => [
                    'maintainance_days' => $maintainance_days,
                    'years'             => $yearsStatus,
                    'dynamic_year'      => $yearRange,
                ],
            ]);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid maintenance type',
                'data'    => [],
            ]);
        }
    }

}
