<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{

    public function searchNewUser(Request $request)
    {
        $users = User::with('organization:id,name')->where('role', $request->role);
        if ($request->organization_id) {
            $users = $users->where('organization_id', $request->organization_id);
        }
        if ($request->search) {
            $users = $users->where('name', 'LIKE', '%' . $request->search . '%')->orWher('email', 'LIKE', '%' . $request->search . '%');
        }

        $users = $users->get();
        return response()->json([
            'status'  => true,
            'message' => 'Data retrieve successfully',
            'data'    => $users,
        ]);
    }


    // public function chatList(Request $request)
    // {
    //     $userId   = Auth::user()->id;
    //     $role     = $request->role;
    //     $search   = $request->search;
    //     $chatList = Message::with(['receiver:id,name,image', 'sender:id,name,image'])->where(function ($query) use ($userId) {
    //         $query->where('sender_id', $userId)
    //             ->orWhere('receiver_id', $userId);
    //     });
    //     if ($role) {
    //         $chatList = $chatList->where(function ($query) use ($role, $search) {
    //             $query->whereHas('receiver', function ($q) use ($role, $search) {
    //                 $q->where('role', $role);
    //                 if ($search) {
    //                     $q->where(function ($q) use ($search) {
    //                         $q->where('name', 'like', '%' . $search . '%');
    //                     });
    //                 }
    //             });
    //         });
    //     }
    //     $chatList = $chatList->latest('created_at')->get()->unique(function ($message) use ($userId) {
    //         return $message->sender_id === $userId
    //         ? $message->receiver_id
    //         : $message->sender_id;
    //     })->values()->toArray();

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'Message retrieve successfully.',
    //         'data'    => $chatList,
    //     ]);
    // }


    public function chatList(Request $request)
    {
        $userId   = Auth::user()->id;
        $roleType = $request->role; // Either 'USER' or 'PROFESSIONAL'
        $search   = $request->search;

        // Base query with eager loading
        $chatList = Message::with([

            'receiver:id,name,image', 'sender:id,name,image',
            'sender:id,name,image', 'sender:id,name,image'
        ])
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            });

        // Apply role type filtering
        if ($roleType) {
            $chatList = $chatList->where(function ($query) use ($roleType, $search) {
                $query->whereHas('receiver', function ($q) use ($roleType, $search) {
                    $q->where('role', $roleType);
                    if ($search) {
                        $q->where(function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                    }
                })->orWhereHas('sender', function ($q) use ($roleType, $search) {
                    $q->where('role', $roleType);
                    if ($search) {
                        $q->where(function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                    }
                });
            });
        }

        // Fetch and remove duplicate chat entries
        $chatList = $chatList->latest('created_at')->get()->unique(function ($message) use ($userId) {
            return $message->sender_id === $userId
            ? $message->receiver_id// Use receiver_id if the sender is the authenticated user
            : $message->sender_id; // Use sender_id if the receiver is the authenticated user
        })->values();

        // Format the response to show the other user's info only
        $chatList = $chatList->map(function ($message) use ($userId) {
            if ($message->sender_id === $userId) {
                // If authenticated user is the sender, show receiver info
                $message->user = $message->receiver;
            } else {
                // If authenticated user is the receiver, show sender info
                $message->user = $message->sender;
            }

            // Optionally remove sender and receiver from response if not needed
            unset($message->sender);
            unset($message->receiver);

            return $message;
        });

        // Standard JSON response
        return response()->json([
            'status'    => true,
            'chat_list' => $chatList,
        ]);
    }

























    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|numeric',
            'message'     => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error', $validator->errors()]);
        }
        $message = Message::create([
            'sender_id'   => Auth::user()->id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'is_read'     => 0,
        ]);
        return response()->json([
            'status'  => true,
            'message' => 'Message saved successfully',
            'data'    => $message], 200);
    }


    public function getMessage(Request $request)
    {
        $per_page = $request->per_page ?? 10;
              $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error', $validator->errors()]);
        }

        $messages = Message::where(function ($query) use ($request) {
            $query->where('sender_id', Auth::id())
                ->where('receiver_id', $request->receiver_id);
        })
            ->orWhere(function ($query) use ($request) {
                $query->where('sender_id', $request->receiver_id)
                    ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate($per_page);

        return response()->json([
            'status'  => true,
            'message' => 'Messages retrieved successfully',
            'data'    => $messages,
        ]);
    }


    public function markRead()
    {
        Message::where('sender_id', Auth::user()->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
        return response([
            'status'  => true,
            'message' => 'Message read successfully',
        ]);
    }


}
