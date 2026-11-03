<?php

namespace Database\Factories;

use App\Enums\SemesterType;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->bothify('???###')),
            'title' => fake()->unique()->sentence(3),
            'credit_units' => fake()->numberBetween(1, 6),
            'level' => fake()->randomElement([100, 200, 300, 400, 500]),
            'semester_type' => fake()->randomElement(SemesterType::cases())->value,
            'is_active' => true,
        ];
    }
}
