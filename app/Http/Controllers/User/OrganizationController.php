<?php
namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

//this controller for organization and third party
class OrganizationController extends Controller
{
    //create or add location employee
    public function addLocationEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }
        // Upload new documents
        $newDocuments = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . '_' . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);
                $newDocuments[] = $documentName;
            }
        }

        $location_employee = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'location_employee',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),
        ]);

        $location_employee->save();

        return response()->json(['status' => true, 'message' => 'location_employee Create Successfully', 'location_employee' => $location_employee], 200);
    }
    //update location_employee
    public function updateLocationEmployee(Request $request, $id)
    {

        $currentUser = auth()->user();

        $location_employee = User::where('id', $id)
            ->where('creator_id', $currentUser->id)
            ->first();

        if (! $location_employee) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'address'  => 'nullable|string',
            'document' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }
        $validatedData              = $validator->validated();
        $location_employee->name    = $validatedData['name'] ?? $location_employee->name;
        $location_employee->address = $validatedData['address'] ?? $location_employee->address;
        $location_employee->phone   = $validatedData['phone'] ?? $location_employee->phone;

        if (! empty($validatedData['password'])) {
            $location_employee->password = Hash::make($validatedData['password']);
        }
        if ($request->hasFile('image')) {
            $existingImage = $location_employee->image;

            if ($existingImage) {
                $oldImage = parse_url($existingImage);
                $filePath = ltrim($oldImage['path'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete the existing image
                }
            }

            // Upload new image
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newName   = time() . '.' . $extension;
            $image->move(public_path('uploads/profile_images'), $newName);

            $location_employee->image = $newName;
        }
        //delete old document
        if ($request->hasFile('documents')) {
            $existingDocuments = $location_employee->document;

            if (is_array($existingDocuments)) {
                foreach ($existingDocuments as $document) {
                    $relativePath = parse_url($document, PHP_URL_PATH);
                    $relativePath = ltrim($relativePath, '/');
                    unlink(public_path($relativePath));
                }
            }

            // Upload new documents
            $newDocuments = [];
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);

                $newDocuments[] = $documentName;
            }

            $location_employee->document = json_encode($newDocuments);
        }
        $location_employee->save();

        return response()->json(['status' => true, 'message' => 'Location Employee Update Successfully', 'location_employee' => $location_employee], 200);
    }
    //create or add Support agent
    public function addSupportAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }
        // Upload new documents
        $newDocuments = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . '_' . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);
                $newDocuments[] = $documentName;
            }
        }

        $support_agent = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'support_agent',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),
        ]);

        $support_agent->save();

        return response()->json(['status' => true, 'message' => 'Support Agent Create Successfully', 'support_agent' => $support_agent], 200);
    }
    //update support_agent
    public function updateSupportAgent(Request $request, $id)
    {
        $currentUser = auth()->user();

        $support_agent = User::where('id', $id)
            ->where('creator_id', $currentUser->id)
            ->first();

        if (! $support_agent) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }
        // $support_agent = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'address'  => 'nullable|string',
            'document' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }
        $validatedData          = $validator->validated();
        $support_agent->name    = $validatedData['name'] ?? $support_agent->name;
        $support_agent->address = $validatedData['address'] ?? $support_agent->address;
        $support_agent->phone   = $validatedData['phone'] ?? $support_agent->phone;

        if (! empty($validatedData['password'])) {
            $support_agent->password = Hash::make($validatedData['password']);
        }
        if ($request->hasFile('image')) {
            $existingImage = $support_agent->image;

            if ($existingImage) {
                $oldImage = parse_url($existingImage);
                $filePath = ltrim($oldImage['path'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete the existing image
                }
            }

            // Upload new image
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newName   = time() . '.' . $extension;
            $image->move(public_path('uploads/profile_images'), $newName);

            $support_agent->image = $newName;
        }
        //delete old document
        if ($request->hasFile('documents')) {
            $existingDocuments = $support_agent->document;

            if (is_array($existingDocuments)) {
                foreach ($existingDocuments as $document) {
                    $relativePath = parse_url($document, PHP_URL_PATH);
                    $relativePath = ltrim($relativePath, '/');
                    unlink(public_path($relativePath));
                }
            }

            // Upload new documents
            $newDocuments = [];
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);

                $newDocuments[] = $documentName;
            }

            $support_agent->document = json_encode($newDocuments);
        }
        $support_agent->save();

        return response()->json(['status' => true, 'message' => 'Support Agent Update Successfully', 'support_agent' => $support_agent], 200);
    }
    //create or add technicain
    public function addTechnician(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }
        // Upload new documents
        $newDocuments = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . '_' . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);
                $newDocuments[] = $documentName;
            }
        }

        $technician = User::create([
            'organization_id'=>Auth::user()->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'technician',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),
        ]);

        $technician->save();

        return response()->json(['status' => true, 'message' => 'TTechnician Create Successfully', 'technician' => $technician], 200);
    }
    //update support_agent
    public function updateTechnician(Request $request, $id)
    {
        $currentUser = auth()->user();

        $technician = User::where('id', $id)
            ->where('creator_id', $currentUser->id)
            ->first();

        if (! $technician) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }
        $technician = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'address'  => 'nullable|string',
            'document' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 401);
        }
        $validatedData       = $validator->validated();
        $technician->name    = $validatedData['name'] ?? $technician->name;
        $technician->address = $validatedData['address'] ?? $technician->address;
        $technician->phone   = $validatedData['phone'] ?? $technician->phone;

        if (! empty($validatedData['password'])) {
            $technician->password = Hash::make($validatedData['password']);
        }
        if ($request->hasFile('image')) {
            $existingImage = $technician->image;

            if ($existingImage) {
                $oldImage = parse_url($existingImage);
                $filePath = ltrim($oldImage['path'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete the existing image
                }
            }

            // Upload new image
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newName   = time() . '.' . $extension;
            $image->move(public_path('uploads/profile_images'), $newName);

            $technician->image = $newName;
        }
        //delete old document
        if ($request->hasFile('documents')) {
            $existingDocuments = $technician->document;

            if (is_array($existingDocuments)) {
                foreach ($existingDocuments as $document) {
                    $relativePath = parse_url($document, PHP_URL_PATH);
                    $relativePath = ltrim($relativePath, '/');
                    unlink(public_path($relativePath));
                }
            }

            // Upload new documents
            $newDocuments = [];
            foreach ($request->file('documents') as $document) {
                $documentName = time() . uniqid() . $document->getClientOriginalName();
                $document->move(public_path('uploads/documents'), $documentName);

                $newDocuments[] = $documentName;
            }

            $technician->document = json_encode($newDocuments);
        }
        $technician->save();

        return response()->json(['status' => true, 'message' => 'Support Agent Update Successfully', 'technician' => $technician], 200);
    }
    //get specifiq user
    public function getuserDetails(Request $request, $id)
    {
        $currentUser = auth()->user();

        $user = User::with('creator:id,name')->find($id);

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }

        // Restrict access for "organization" and "third_party" roles
        if (in_array($currentUser->role, ['organization', 'third_party']) && $user->creator_id != $currentUser->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized Access'], 401);
        }

        $data = [
            'id'           => $user->id,
            'name'         => $user->name,
            'image'        => $user->image,
            'email'        => $user->email,
            'contact'      => $user->phone,
            'address'      => $user->address,
            'organization' => $user->creator->name ?? 'N/A',
        ];

        return response()->json(['status' => true, 'message' => $data], 200);
    }

    //   delete location employee, technician and support agent
    public function deleteSpecificUser($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 401);
        }

        if (! in_array($user->role, ['technician', 'location_employee', 'support_agent'])) {
            return response()->json(['status' => 'error', 'message' => 'User cannot be deleted.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }



    public function getOrganization(){
        $organizations=User::where('role','organization')->select('id','name')->get();
        return response()->json([
            'status'=>true,
            'message'=>'Organization retreived successfully.',
            'data'=>$organizations,
        ]);
    }

}
