<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_service_id' => BusinessService::factory(),
            'service_schedule_id' => ServiceSchedule::factory(),
            'booking_date' => today()->addDays(7),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'total_cost' => 100000,
            'booking_code' => 'BK-'.fake()->unique()->bothify('##########'),
            'status' => 'held',
            'expires_at' => now()->addMinutes(10),
            'paid_at' => null,
            'payment_proof' => null,
            'payment_submitted_at' => null,
            'verified_at' => null,
            'verification_note' => null,
            'notes' => null,
        ];
    }
}
