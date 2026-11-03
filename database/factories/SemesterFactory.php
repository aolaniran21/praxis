<?php

namespace Database\Factories;

use App\Enums\SemesterType;
use App\Models\AcademicSession;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Semester> */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        return [
            'academic_session_id' => AcademicSession::factory(),
            'type' => SemesterType::First->value,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->addMonths(4)->endOfMonth()->toDateString(),
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDay(),
            'max_credit_units' => 24,
            'is_current' => false,
            'is_active' => true,
        ];
    }
}
