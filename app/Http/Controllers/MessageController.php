<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
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
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error', $validator->errors()]);
        }
        $messages = Message::where('sender_id', Auth::user()->id)->where('receiver_id', $request->receiver_id)
            ->paginate();
        return response()->json($messages);
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

    public function searchNewUser(Request $request)
    {
        $user = User::where('role', $request->role)->where('name', 'LIKE', '%' . $request->search . '%')->get();
        return response()->json([
            'status'  => true,
            'message' => 'Data retrieve successfully',
            'data'    => $user,
        ]);
    }

    public function chatList(Request $request)
    {
        $userId   = Auth::user()->id;
        $role     = $request->role;
        $search   = $request->search;
        $chatList = Message::with(['receiver:id,name,image', 'sender:id,name,image'])->where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        });
        if ($role) {
            $chatList = $chatList->where(function ($query) use ($role, $search) {
                $query->whereHas('receiver', function ($q) use ($role, $search) {
                    $q->where('role', $role);
                    if ($search) {
                        $q->where(function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                    }
                });
            });
        }
        $chatList = $chatList->latest('created_at')->get()->unique(function ($message) use ($userId) {
            return $message->sender_id === $userId
            ? $message->receiver_id
            : $message->sender_id;
        })->values()->toArray();

        return response()->json([
            'status'  => true,
            'message' => 'Message retrieve successfully.',
            'data'    => $chatList,
        ]);
    }
}
