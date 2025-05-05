<?php

namespace Database\Seeders;

use App\Models\FAQ;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FAQSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FAQ::insert([
            [
                'question'   => 'What services do you offer?',
                'answer'     => 'We offer a variety of services, including home maintenance, repair services, and professional consultations.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'question'   => 'How can I book a service?',
                'answer'     => 'You can book a service through our website or mobile app by selecting your desired service and providing the necessary details.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'question'   => 'What payment methods do you accept?',
                'answer'     => 'We accept credit cards, debit cards, and online payment methods such as PayPal and Stripe.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'question'   => 'Can I reschedule my booking?',
                'answer'     => 'Yes, you can reschedule your booking by contacting our support team at least 24 hours in advance.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'question'   => 'Do you offer refunds?',
                'answer'     => 'Refunds are available under certain conditions. Please refer to our refund policy for more details.',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
