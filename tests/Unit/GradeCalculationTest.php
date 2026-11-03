<?php

use App\Services\GradeCalculationService;
use Illuminate\Validation\ValidationException;

it('applies every grade boundary', function (int $ca, int $exam, string $grade, float $point) {
    $service = new GradeCalculationService;
    $total = $ca + $exam;

    expect($service->calculate($ca, $exam))->toBe([
        'total_score' => $total,
        'grade' => $grade,
        'grade_point' => $point,
    ]);
})->with([
    'zero' => [0, 0, 'F', 0.0],
    '39 remains an F' => [0, 39, 'F', 0.0],
    '40 starts an E' => [0, 40, 'E', 1.0],
    '44 remains an E' => [0, 44, 'E', 1.0],
    '45 starts a D' => [0, 45, 'D', 2.0],
    '49 remains a D' => [0, 49, 'D', 2.0],
    '50 starts a C' => [0, 50, 'C', 3.0],
    '59 remains a C' => [0, 59, 'C', 3.0],
    '60 starts a B' => [0, 60, 'B', 4.0],
    '69 remains a B' => [0, 69, 'B', 4.0],
    '70 starts an A' => [0, 70, 'A', 5.0],
    '100 is an A' => [30, 70, 'A', 5.0],
]);

it('rejects component scores outside their configured ranges', function (int $ca, int $exam) {
    expect(fn () => (new GradeCalculationService)->calculate($ca, $exam))
        ->toThrow(ValidationException::class);
})->with([
    'negative CA' => [-1, 50],
    'CA over 30' => [31, 50],
    'negative exam' => [10, -1],
    'exam over 70' => [10, 71],
]);
