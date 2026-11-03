<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AcademicReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultStatementController extends Controller
{
    public function __invoke(Request $request, AcademicReportService $reports): View
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'A student profile has not been assigned to this account.');

        return view('student.results.index', [
            ...$reports->resultStatement($student),
            'student' => $student->load('user', 'program.department'),
        ]);
    }
}
