<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\CourseRegistration;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Result> */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'course_registration_id' => CourseRegistration::factory(),
            'ca_score' => 25,
            'exam_score' => 45,
            'total_score' => 70,
            'grade' => 'A',
            'grade_point' => 5,
            'status' => ResultStatus::Draft->value,
        ];
    }
}
