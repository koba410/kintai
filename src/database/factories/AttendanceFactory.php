<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'check_in' => now()->setTime(9, 0),
            'check_out' => now()->setTime(18, 0),
            'attendance_status' => '退勤済',
        ];
    }
}
