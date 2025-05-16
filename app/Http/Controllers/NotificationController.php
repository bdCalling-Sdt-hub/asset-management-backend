<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function notifications()
    {
        $notifications = Auth::user()->notifications;
        return response()->json([
            'status' => true,
            'message'=>'Notification retreived successfully.',
            'data'   => $notifications,
        ], 200);
    }

    public function singleMark($notification_id)
    {
        try {
            $notification = Auth::user()->unreadNotifications->where('id', $notification_id)->first();
            if (! $notification) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Notification not found or already marked as read.',
                ], 404);
            }

            $notification->markAsRead();
            return response()->json([
                'status'  => true,
                'message' => 'Notification marked as read successfully.',
                'data'    => $notification,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while marking the notification.',
            ], 500);
        }
    }

    public function allMark()
    {
        try {
            $notifications = Auth::user()->unreadNotifications;

            if ($notifications->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No unread notifications found.',
                ], 404);
            }

            $notifications->markAsRead();

            return response()->json([
                'status'  => true,
                'message' => 'All notifications marked as read successfully.',
                'data'    => $notifications,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while marking notifications.',
            ], 500);
        }
    }
}
