<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Lecturer;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseAssignment> */
class CourseAssignmentFactory extends Factory
{
    protected $model = CourseAssignment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'lecturer_id' => Lecturer::factory(),
            'semester_id' => Semester::factory(),
            'is_active' => true,
        ];
    }
}
