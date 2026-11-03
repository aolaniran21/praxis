<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Collection;

class AcademicReportService
{
    /**
     * Returns the latest published attempt for each course, so repeated attempts
     * do not inflate the cumulative credit total.
     *
     * @return array{courses: Collection<int, Result>, cgpa: float}
     */
    public function resultStatement(Student $student): array
    {
        $latestResults = $student->courseRegistrations()
            ->with(['course', 'semester.academicSession', 'result'])
            ->whereHas('result', fn ($query) => $query->where('status', ResultStatus::Published->value))
            ->get()
            ->map(fn ($registration) => $registration->result)
            ->groupBy(fn ($result) => $result->courseRegistration->course_id)
            ->map(fn (Collection $attempts) => $attempts
                ->sort(function (Result $left, Result $right): int {
                    $leftSemester = $left->courseRegistration->semester;
                    $rightSemester = $right->courseRegistration->semester;
                    $leftOrder = [
                        $leftSemester->starts_on->getTimestamp(),
                        $leftSemester->academicSession->starts_on->getTimestamp(),
                        $leftSemester->id,
                    ];
                    $rightOrder = [
                        $rightSemester->starts_on->getTimestamp(),
                        $rightSemester->academicSession->starts_on->getTimestamp(),
                        $rightSemester->id,
                    ];

                    return $rightOrder <=> $leftOrder;
                })
                ->first())
            ->values()
            ->sortBy(fn ($result) => $result->courseRegistration->course->code)
            ->values();

        $creditUnits = $latestResults->sum(fn ($result) => $result->courseRegistration->course->credit_units);
        $weightedPoints = $latestResults->sum(
            fn ($result) => $result->grade_point * $result->courseRegistration->course->credit_units,
        );

        return [
            'courses' => $latestResults,
            'cgpa' => $creditUnits > 0
                ? round($weightedPoints / $creditUnits, 2, PHP_ROUND_HALF_UP)
                : 0.0,
        ];
    }
}
