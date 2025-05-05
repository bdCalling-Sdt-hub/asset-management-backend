<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch two users (sender and receiver)
        $sender = User::where('role', 'user')->first();
        $receiver = User::where('role', 'user')->skip(1)->first(); // You can modify this to get any two users

        $senderId = $sender ? $sender->id : null;
        $receiverId = $receiver ? $receiver->id : null;

        // Ensure we have valid foreign keys before creating records
        if (!$senderId || !$receiverId) {
            return;
        }

        // Sample messages
        $messages = [
            [
                'sender_id'   => $senderId,
                'receiver_id' => $receiverId,
                'message'     => 'Hello, how are you?',
                'is_read'     => false,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'sender_id'   => $receiverId,
                'receiver_id' => $senderId,
                'message'     => 'I am doing great, thanks for asking!',
                'is_read'     => false,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'sender_id'   => $senderId,
                'receiver_id' => $receiverId,
                'message'     => 'I wanted to ask if you are free to meet up later.',
                'is_read'     => false,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]
        ];

        // Insert data
        foreach ($messages as $message) {
            Message::create($message);
        }
    }
}
