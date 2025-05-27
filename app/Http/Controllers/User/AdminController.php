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
    public function addUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'organization_id' => 'required|numeric|exists:users,id',
            'email'           => 'required|string|email|unique:users,email',
            'password'        => 'required|string|min:6',
            'role'            => 'required|in:organization,support_agent,location_employee,technician,third_party',
            'address'         => 'required|string',
            'image'           => 'required|mimes:jpg,png,jpeg|max:10240',
            'documents.*'     => 'sometimes|file',
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
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newName   = time() . '.' . $extension;
            $image->move(public_path('uploads/profile_images'), $newName);
        }
        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'role'            => $request->role,
            'password'        => Hash::make($request->password),
            'image'           => $newName,
            'address'         => $request->address,
            'document'        => json_encode($newDocuments),
            'organization_id' => $request->organization_id,
            'creator_id'      => Auth::user()->id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => $request->role . ' Created Successfully',
            'data'    => $user,
        ], 200);
    }

    public function updateUser(Request $request, $oldImage)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'organization_id' => 'required|numeric|exists:users,id',
            'email'           => 'required|string|email|unique:users,email',
            'password'        => 'required|string|min:6',
            'address'         => 'required|string',
            'photo'           => 'sometimes|mimes:jpg,png,jpeg|max:10240',
            'documents.*'     => 'sometimes|file',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }
        $user = User::findOrFail($request->id);
        if ($request->hasFile('documents')) {
            $existingDocuments = $user->document;

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

            $user->document = json_encode($newDocuments);
        }
        if ($request->hasFile('image')) {
            $existingImage = $user->image;

            if ($existingImage) {
                $oldImage = parse_url($existingImage);
                $filePath = ltrim($oldImage['path'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newName   = time() . '.' . $extension;
            $image->move(public_path('uploads/profile_images'), $newName);

            $user->image = $newName;
        }
        $user->name            = $request->name;
        $user->email           = $request->email;
        $user->address         = $request->address;
        $user->organization_id = $request->organization_id;

        if (! empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        if (! empty($newDocuments)) {
            $user->document = json_encode($newDocuments);
        }

        $user->save();
        return response()->json([
            'status'  => true,
            'message' => $request->role . ' updated Successfully',
            'data'    => $user,
        ], 200);
    }

    //get all user
    public function userList(Request $request)
    {
        $search = $request->input('search');
        $role   = $request->input('role');
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

        if ($role) {
            $userlist->where('role', $role);
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

    public function getProviders(Request $request)
    {
        $role          = $request->role;
        $paginate      = $request->paginate ?? 10;
        $search        = $request->search;
        $sort          = $request->sort;
        $sortDirection = 'asc';

        $validRoles = ['third_party', 'technician', 'location_employee', 'support_agent', 'organization'];

        if (! in_array($role, $validRoles)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid role provided.',
                'data'    => null,
            ], 400);
        }

        $query = User::select('users.id', 'users.organization_id', 'users.name', 'users.email', 'users.phone', 'users.image', 'users.address', 'users.role')
            ->where('role', $role);

        if ($role === 'organization') {
            $query->withCount(['technicians', 'supportAgents', 'locationEmployees']);
        } else {
            $query->with('organization:id,name,role');
        }
        if ($search) {
            $query->where(function ($q) use ($search, $role) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%");

                if ($role !== 'organization') {
                    $q->orWhereHas('organization', function ($orgQuery) use ($search) {
                        $orgQuery->where('name', 'like', "%$search%");
                    });
                }
            });
        }

        $allowedSortFields = ['id', 'name', 'address'];

        if (in_array($sort, $allowedSortFields)) {
            $query->orderBy($sort, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        $users = $query->paginate($paginate);

        return response()->json([
            'status'  => true,
            'message' => ucfirst($role) . 's retrieved successfully',
            'data'    => $users,
        ]);
    }

}
