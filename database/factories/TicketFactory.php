<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::inRandomOrder()->first()->id ?? User::factory(),
            'asset_id'      => Asset::inRandomOrder()->first()->id ?? Asset::factory(),
            'problem'       => $this->faker->sentence(),
            'ticket_type'   => $this->faker->randomElement(['New Tickets', 'Open Tickets', 'Past Tickets']),
            'user_comment'  => $this->faker->text(100),
            'ticket_status' => $this->faker->randomElement(['New', 'Assigned', 'Completed']),
            'cost'          => $this->faker->randomFloat(2, 50, 1000),
            'order_number'  => $this->faker->unique()->numerify('ORD-######'),
        ];
    }
}
