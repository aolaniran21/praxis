<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lecturer> */
class LecturerFactory extends Factory
{
    protected $model = Lecturer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'staff_number' => strtoupper(fake()->unique()->bothify('STAFF-#######')),
        ];
    }
}
