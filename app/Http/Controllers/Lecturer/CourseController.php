<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = $request->user()->lecturer;
        abort_unless($lecturer, 403, 'A lecturer profile has not been assigned to this account.');

        $assignments = CourseAssignment::query()
            ->with('course', 'semester.academicSession')
            ->where('lecturer_id', $lecturer->id)
            ->latest('semester_id')
            ->get();

        return view('lecturer.courses.index', compact('assignments'));
    }

    public function show(CourseAssignment $courseAssignment): View
    {
        $this->authorize('view', $courseAssignment);

        $registrations = $courseAssignment->course
            ->registrations()
            ->with(['student.user', 'result'])
            ->where('semester_id', $courseAssignment->semester_id)
            ->orderBy('student_id')
            ->get();

        return view('lecturer.courses.show', compact('courseAssignment', 'registrations'));
    }
}
