<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    //create or add organization
    public function addOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
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

        $organization = User::create([
            'user_id'    => Auth::user()->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'organization',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),
        ]);

        $organization->save();

        return response()->json(['status' => true, 'message' => 'Organization Create Successfully', 'organization' => $organization], 200);
    }
    //update organization
    public function updateOrganization(Request $request, $id)
    {
        $organization = User::find($id);

        if (!$organization) {
            return response()->json(['status' => false, 'message' => 'User not found'], 200);
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
        $validatedData         = $validator->validated();
        $organization->name    = $validatedData['name'] ?? $organization->name;
        $organization->address = $validatedData['address'] ?? $organization->address;
        $organization->phone   = $validatedData['phone'] ?? $organization->phone;

        if (! empty($validatedData['password'])) {
            $organization->password = Hash::make($validatedData['password']);
        }
        if ($request->hasFile('image')) {
            $existingImage = $organization->image;

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

            $organization->image = $newName;
        }
        //delete old document
        if ($request->hasFile('documents')) {
            $existingDocuments = $organization->document;

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

            $organization->document = json_encode($newDocuments);
        }
        $organization->save();

        return response()->json(['status' => true, 'message' => 'Organization Update Successfully', 'organization' => $organization], 200);
    }
    //create or add third party
    public function addThirdParty(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
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

        $third_party = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'third_party',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),

        ]);

        $third_party->save();

        return response()->json(['status' => true, 'message' => 'Third Party Create Successfully', 'third_party' => $third_party], 200);
    }
    //update third party
    public function updateThirdParty(Request $request, $id)
    {
        $third_party = User::find($id);
        if (!$third_party) {
            return response()->json(['status' => false, 'message' => 'User not found'], 200);
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
        $validatedData        = $validator->validated();
        $third_party->name    = $validatedData['name'] ?? $third_party->name;
        $third_party->address = $validatedData['address'] ?? $third_party->address;
        $third_party->phone   = $validatedData['phone'] ?? $third_party->phone;

        if (! empty($validatedData['password'])) {
            $third_party->password = Hash::make($validatedData['password']);
        }
        if ($request->hasFile('image')) {
            $existingImage = $third_party->image;

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

            $third_party->image = $newName;
        }
        //delete old document
        if ($request->hasFile('documents')) {
            $existingDocuments = $third_party->document;

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

            $third_party->document = json_encode($newDocuments);
        }
        $third_party->save();

        return response()->json(['status' => true, 'message' => 'third_party Update Successfully', 'third_party' => $third_party], 200);
    }
    //location employee add
    public function addEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
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

        return response()->json(['status' => true, 'message' => 'location_employee Create Successfully', 'location employee' => $location_employee], 200);
    }
    //update location mployee
    public function updateEmployee(Request $request, $id)
    {
        $location_employee = User::find($id);
        if (!$location_employee) {
            return response()->json(['status' => false, 'message' => 'User not found'], 200);
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
    public function addAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
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
    public function updateSAgent(Request $request, $id)
    {
        $support_agent = User::find($id);
        if (!$support_agent) {
            return response()->json(['status' => false, 'message' => 'User not found'], 200);
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
                    if (! file_exists(public_path('uploads/documents'))) {
                        # code...
                        unlink(public_path($relativePath));
                    }
                }
            }
            // return $existingDocuments;

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
    public function technicianAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'address'     => 'required|string',
            'documents.*' => 'nullable|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
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
            'name'       => $request->name,
            'email'      => $request->email,
            'role'       => 'technician',
            'password'   => Hash::make($request->password),
            'address'    => $request->address,
            'document'   => json_encode($newDocuments),
            'creator_id' => auth()->id(),

        ]);

        $technician->save();

        return response()->json(['status' => true, 'message' => 'Support Agent Create Successfully', 'technician' => $technician], 200);
    }
    //update support_agent
    public function technicianUpdate(Request $request, $id)
    {
        $technician = User::findOrFail($id);

        if (!$technician) {
            return response()->json(['status' => false, 'message' => 'User not found'], 200);
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
    //get all user
    public function userList(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter');
        $sortBy = $request->input('sort_by');

        $currentUser = auth()->user();
        $userlist    = User::query();

        if ($currentUser->role === 'super_admin') {
            $userlist->where('id', '!=', $currentUser->id);
        } elseif (in_array($currentUser->role, ['organization', 'third_party'])) {
            $userlist->where('creator_id', $currentUser->id);
        }

        if (! empty($search)) {
            $userlist->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%");
            });
        }

        if (! empty($sortBy)) {
            $allowedSorts = ['id', 'name', 'address', 'organization'];
            if (in_array($sortBy, $allowedSorts)) {
                $userlist->orderBy($sortBy, $sortBy === 'address' ? 'asc' : 'desc');
            }
        } else {
            $userlist->orderBy('id', 'asc');
        }

        if (! empty($filter)) {
            $userlist->where('role', $filter);
        }

        $users = $userlist->paginate(10);

        $data = $users->getCollection()->map(function ($user) {
            $commonData = [
                'id'      => $user->id,
                'name'    => $user->name,
                'role'    => $user->role,
                'address' => $user->address,
                'image'   => $user->image,
            ];

            if ($user->role === 'organization' || $user->role === 'third_party') {
                // Count all users created by this organization
                $totalUsersCreated = User::where('creator_id', $user->id)->count();

                return array_merge($commonData, [
                    'total_users_created' => $totalUsersCreated,
                    // 'location_employee' => User::where('role', 'location_employee')->where('creator_id', $user->id)->count(),
                    // 'support_agent' => User::where('role', 'support_agent')->where('creator_id', $user->id)->count(),
                    // 'technician' => User::where('role', 'technician')->where('creator_id', $user->id)->count(),
                ]);
            } else {
                return array_merge($commonData, [
                    'creator' => $user->creator->name ?? 'N/A',
                ]);
            }
        });

        $users->setCollection(collect($data));

        return response()->json(['status' => true, 'data' => $users]);
    }

    public function userDetails(Request $request, $id)
    {
        $user = User::with('creator:id,name')->find($id);

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'User Not Found'], 401);
        }
        $data = [
            'id'      => $user->id,
            'name'    => $user->name,
            'role'    => $user->role,
            'email'   => $user->email,
            'contact' => $user->phone,
            'address' => $user->address,
            'creator' => $user->creator->name ?? 'N/A',
        ];

        // add role org the count of location_employee or support_agent
        if (in_array($user->role, ['organization', 'third_party'])) {
            // Count the number of location_employee, support_agent, technician,
            $locationEmployeeCount = User::where('role', 'location_employee')->where('creator_id', $user->id)->count();
            $supportAgentCount     = User::where('role', 'support_agent')->where('creator_id', $user->id)->count();
            $technicianCount       = User::where('role', 'technician')->where('creator_id', $user->id)->count();

            // Add these $data
            $data['location employee'] = $locationEmployeeCount;
            $data['support agent']     = $supportAgentCount;
            $data['technician']        = $technicianCount;
        }

        return response()->json(['status' => true, 'message' => $data], 200);
    }

    public function deleteUser($id)
    {
        $user = User::where('id', $id)->first();
        if (! $user) {
            return response()->json(['status' => false, 'message' => 'User not found.'], 401);
        }

        $user->delete();

        return response()->json(['status' => true, 'message' => 'User deleted successfully'], 200);
    }

    public function SoftDeletedUsers()
    {
        $deletedUsers = User::onlyTrashed()->get();

        return response()->json(['status' => true, 'message' => $deletedUsers], 200);
    }
}
