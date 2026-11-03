<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $semester = Semester::query()->with('academicSession')->current()->first();

        if ($user->hasRole(UserRole::Student->value)) {
            abort_unless($user->student, 403, 'A student profile has not been assigned to this account.');

            $metrics = [
                'Registered courses' => $semester
                    ? $user->student->courseRegistrations()->where('semester_id', $semester->id)->count()
                    : 0,
                'Published results' => $user->student->courseRegistrations()
                    ->whereHas('result', fn ($query) => $query->where('status', 'published'))
                    ->count(),
            ];
            $actions = [
                ['label' => 'Course registration', 'route' => 'student.registrations.index'],
                ['label' => 'Result statement', 'route' => 'student.results.index'],
            ];
            $roleLabel = 'Student';
        } elseif ($user->hasRole(UserRole::Lecturer->value)) {
            abort_unless($user->lecturer, 403, 'A lecturer profile has not been assigned to this account.');

            $assignmentQuery = $user->lecturer->courseAssignments();
            $metrics = [
                'Assigned courses' => (clone $assignmentQuery)->when(
                    $semester,
                    fn ($query) => $query->where('semester_id', $semester->id),
                    fn ($query) => $query->whereRaw('1 = 0'),
                )->count(),
                'Submitted results' => Result::query()
                    ->where('status', 'submitted')
                    ->whereHas('courseRegistration.course.assignments', fn ($query) => $query
                        ->where('lecturer_id', $user->lecturer->id)
                        ->when($semester, fn ($query) => $query->where('semester_id', $semester->id)))
                    ->count(),
            ];
            $actions = [['label' => 'Assigned courses', 'route' => 'lecturer.courses.index']];
            $roleLabel = 'Lecturer';
        } elseif ($user->hasRole(UserRole::DepartmentAdmin->value)) {
            abort_unless($user->department_id, 403, 'A department has not been assigned to this account.');

            $metrics = [
                'Department courses' => $user->department->courses()->where('is_active', true)->count(),
                'Results awaiting review' => Result::query()
                    ->where('status', 'submitted')
                    ->whereHas('courseRegistration.course', fn ($query) => $query
                        ->where('department_id', $user->department_id))
                    ->count(),
                'Approved results' => Result::query()
                    ->where('status', 'approved')
                    ->whereHas('courseRegistration.course', fn ($query) => $query
                        ->where('department_id', $user->department_id))
                    ->count(),
            ];
            $actions = [
                ['label' => 'Manage courses', 'route' => 'department.courses.index'],
                ['label' => 'Review results', 'route' => 'department.results.index'],
            ];
            $roleLabel = 'Department administrator';
        } elseif ($user->hasRole(UserRole::SuperAdmin->value)) {
            $metrics = [
                'Faculties' => Faculty::query()->count(),
                'Departments' => Department::query()->count(),
                'Students' => Student::query()->count(),
                'Lecturers' => Lecturer::query()->count(),
            ];
            $actions = [
                ['label' => 'Academic setup', 'route' => 'super-admin.academic.index'],
                ['label' => 'Manage users', 'route' => 'super-admin.users.index'],
                ['label' => 'University reports', 'route' => 'reports.index'],
            ];
            $roleLabel = 'Super administrator';
        } else {
            abort(403, 'No application role is assigned to this account.');
        }

        return view('dashboard', compact('actions', 'metrics', 'roleLabel', 'semester'));
    }
}
