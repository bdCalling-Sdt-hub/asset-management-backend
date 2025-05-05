<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function Address(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        if (!$validator) {
            return response()->json(['status'=>false,'message'=>$validator->errors()],422);
        }
        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return response()->json(['status'=>false,'message'=>'User Not Found'],422);
        }
        // Update User Location
        $user->latitude  = $request->latitude;
        $user->longitude = $request->longitude;
        $user->address   = $request->address ?? $user->address;
        $user->save();

        return response()->json(['status' => true, 'message' => 'Address Updated Successfully!', 'data' => $user]);
    }

    public function getAddress($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }

        return response()->json([
            'status'    => true,
            'latitude'  => $user->latitude,
            'longitude' => $user->longitude,
            'address'   => $user->address
        ]);
    }
}
