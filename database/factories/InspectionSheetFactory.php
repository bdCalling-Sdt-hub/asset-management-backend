<?php

namespace Database\Factories;

use App\Models\InspectionSheet;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InspectionSheet>
 */
class InspectionSheetFactory extends Factory
{
    protected $model = InspectionSheet::class;

    public function definition(): array
    {
        return [
            'support_agent_id'            => User::inRandomOrder()->first()->id ?? User::factory(),
            'ticket_id'                   => Ticket::inRandomOrder()->first()->id ?? Ticket::factory(),
            'technician_id'               => User::where('role', 'technician')->inRandomOrder()->first()->id ?? User::factory(),
            'inspection_sheet_type'       => $this->faker->randomElement(['New Sheets', 'Open Sheets', 'Past Sheets']),
            'support_agent_comment'       => $this->faker->sentence(),
            'technician_comment'          => $this->faker->sentence(),
            'location_employee_signature' => $this->faker->imageUrl(),
            'image'                       => json_encode([$this->faker->imageUrl()]),
            'video'                       => json_encode([$this->faker->url()]),
            'status'                      => $this->faker->randomElement(['New', 'Arrived in Location', 'Contract with user', 'View the problem', 'Solve the problem', 'Completed']),
        ];
    }
}
