<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class GradeCalculationService
{
    /**
     * CA is scored out of 30 and the examination is scored out of 70.
     *
     * @return array{total_score: int, grade: string, grade_point: float}
     */
    public function calculate(int $caScore, int $examScore): array
    {
        if ($caScore < 0 || $caScore > 30 || $examScore < 0 || $examScore > 70) {
            throw ValidationException::withMessages([
                'scores' => 'Continuous assessment must be 0-30 and examination must be 0-70.',
            ]);
        }

        $total = $caScore + $examScore;

        [$grade, $point] = match (true) {
            $total >= 70 => ['A', 5.0],
            $total >= 60 => ['B', 4.0],
            $total >= 50 => ['C', 3.0],
            $total >= 45 => ['D', 2.0],
            $total >= 40 => ['E', 1.0],
            default => ['F', 0.0],
        };

        return [
            'total_score' => $total,
            'grade' => $grade,
            'grade_point' => $point,
        ];
    }
}
