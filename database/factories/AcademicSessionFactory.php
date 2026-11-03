<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicSession> */
class AcademicSessionFactory extends Factory
{
    protected $model = AcademicSession::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2098);

        return [
            'name' => "{$year}/".($year + 1),
            'starts_on' => "{$year}-09-01",
            'ends_on' => ($year + 1).'-08-31',
            'is_active' => false,
        ];
    }
}
