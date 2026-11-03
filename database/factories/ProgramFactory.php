<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->lexify('PRG????')),
            'is_active' => true,
        ];
    }
}
