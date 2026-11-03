<?php

namespace App\Http\Controllers\Student;

use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRegistrationRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Services\CourseRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'A student profile has not been assigned to this account.');

        $semester = Semester::query()
            ->with('academicSession')
            ->current()
            ->whereHas('academicSession', fn ($query) => $query->where('is_active', true))
            ->first();

        $passedCourseIds = $student->courseRegistrations()
            ->whereHas('result', fn ($query) => $query
                ->where('status', ResultStatus::Published->value)
                ->where('total_score', '>=', 40))
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id);

        $courses = $semester && $student->is_active && $student->program->is_active && $student->program->department->is_active
            ? Course::query()
                ->where('department_id', $student->program->department_id)
                ->where('level', $student->level)
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->whereNull('semester_type')
                    ->orWhere('semester_type', $semester->type->value))
                ->with('prerequisites')
                ->orderBy('code')
                ->get()
                ->filter(fn (Course $course) => $course->prerequisites
                    ->every(fn (Course $prerequisite) => $passedCourseIds->contains($prerequisite->id)))
                ->values()
            : collect();

        $registrations = $semester
            ? CourseRegistration::query()
                ->with('course', 'result')
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->orderBy('course_id')
                ->get()
            : collect();

        return view('student.registrations.index', compact('courses', 'registrations', 'semester', 'student'));
    }

    public function store(
        CourseRegistrationRequest $request,
        CourseRegistrationService $registrations,
    ): RedirectResponse {
        $student = $request->user()->student;
        abort_unless($student, 403, 'A student profile has not been assigned to this account.');

        $registrations->register(
            $student,
            array_map('intval', $request->validated('courses')),
        );

        return to_route('student.registrations.index')
            ->with('status', 'Course registration completed.');
    }

    public function drop(
        Request $request,
        CourseRegistration $registration,
        CourseRegistrationService $registrations,
    ): RedirectResponse {
        $this->authorize('drop', $registration);
        $registrations->drop($request->user(), $registration);

        return to_route('student.registrations.index')
            ->with('status', 'Course dropped. You may add it again before registration closes.');
    }
}
