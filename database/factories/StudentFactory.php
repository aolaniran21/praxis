<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'program_id' => Program::factory(),
            'student_number' => strtoupper(fake()->unique()->bothify('STU-########')),
            'level' => fake()->randomElement([100, 200, 300, 400, 500]),
            'is_active' => true,
        ];
    }
}
