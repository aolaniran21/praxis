<?php

namespace App\Http\Controllers\Department;

use App\Enums\SemesterType;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseManagementController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403, 'A department has not been assigned to this account.');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        return view('department.courses.index', [
            'courses' => Course::query()
                ->with('prerequisites')
                ->where('department_id', $departmentId)
                ->orderBy('code')
                ->get(),
            'courseList' => Course::query()
                ->where('department_id', $departmentId)
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query
                    ->where(fn ($query) => $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")))
                ->when(isset($filters['active']), fn ($query) => $query
                    ->where('is_active', (bool) $filters['active']))
                ->with('prerequisites')
                ->orderBy('code')
                ->paginate(20)
                ->withQueryString(),
            'lecturers' => Lecturer::query()
                ->with('user')
                ->where('department_id', $departmentId)
                ->orderBy('staff_number')
                ->get(),
            'semesters' => Semester::query()
                ->with('academicSession')
                ->orderByDesc('starts_on')
                ->get(),
            'assignments' => CourseAssignment::query()
                ->with('course', 'lecturer.user', 'semester.academicSession')
                ->whereHas('course', fn ($query) => $query->where('department_id', $departmentId))
                ->latest('semester_id')
                ->paginate(20, ['*'], 'assignment_page')
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);
        abort_unless(Department::query()->whereKey($departmentId)->where('is_active', true)->exists(), 403, 'This department is archived.');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:courses,code'],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'between:1,30'],
            'level' => ['required', 'integer', Rule::in([100, 200, 300, 400, 500, 600, 700, 800, 900])],
            'semester_type' => ['nullable', Rule::enum(SemesterType::class)],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'distinct', 'exists:courses,id'],
        ]);

        $prerequisiteIds = $data['prerequisites'] ?? [];
        $validPrerequisiteCount = Course::query()
            ->where('department_id', $departmentId)
            ->whereIn('id', $prerequisiteIds)
            ->count();

        if ($validPrerequisiteCount !== count($prerequisiteIds)) {
            throw ValidationException::withMessages([
                'prerequisites' => 'Prerequisites must be courses in your department.',
            ]);
        }

        DB::transaction(function () use ($data, $prerequisiteIds, $departmentId): void {
            $course = Course::query()->create([
                ...collect($data)->except('prerequisites')->all(),
                'department_id' => $departmentId,
                'is_active' => true,
            ]);
            $course->prerequisites()->sync($prerequisiteIds);
        });

        return to_route('department.courses.index')->with('status', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId && (int) $course->department_id === (int) $departmentId, 404);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('courses', 'code')->ignore($course->id)],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'between:1,30'],
            'level' => ['required', 'integer', Rule::in([100, 200, 300, 400, 500, 600, 700, 800, 900])],
            'semester_type' => ['nullable', Rule::enum(SemesterType::class)],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'distinct', 'exists:courses,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $prerequisiteIds = $data['prerequisites'] ?? [];
        $validPrerequisiteCount = Course::query()
            ->where('department_id', $departmentId)
            ->where('id', '!=', $course->id)
            ->whereIn('id', $prerequisiteIds)
            ->count();

        if ($validPrerequisiteCount !== count($prerequisiteIds)) {
            throw ValidationException::withMessages([
                'prerequisites' => 'Prerequisites must be other courses in your department.',
            ]);
        }

        if ($data['is_active'] && ! Department::query()->whereKey($departmentId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'A course cannot be activated while its department is archived.',
            ]);
        }

        if ($course->registrations()->exists()) {
            $currentAttributes = [
                'code' => $course->code,
                'title' => $course->title,
                'credit_units' => $course->credit_units,
                'level' => $course->level,
                'semester_type' => $course->semester_type?->value,
            ];
            $updatedAttributes = [
                'code' => $data['code'],
                'title' => $data['title'],
                'credit_units' => (int) $data['credit_units'],
                'level' => (int) $data['level'],
                'semester_type' => $data['semester_type'] ?? null,
            ];
            $currentPrerequisites = $course->prerequisites()
                ->pluck('courses.id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
            $updatedPrerequisites = array_map('intval', $prerequisiteIds);
            sort($updatedPrerequisites);

            if ($currentAttributes !== $updatedAttributes || $currentPrerequisites !== $updatedPrerequisites) {
                throw ValidationException::withMessages([
                    'course' => 'Course details and prerequisites cannot change after students have registered. Archive it instead.',
                ]);
            }
        }

        DB::transaction(function () use ($course, $data, $prerequisiteIds): void {
            $course->update(collect($data)->except('prerequisites')->all());
            $course->prerequisites()->sync($prerequisiteIds);
        });

        return to_route('department.courses.index')->with('status', 'Course updated.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403);
        abort_unless(Department::query()->whereKey($departmentId)->where('is_active', true)->exists(), 403, 'This department is archived.');

        $data = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('department_id', $departmentId)->where('is_active', true),
            ],
            'lecturer_id' => [
                'required',
                'integer',
                Rule::exists('lecturers', 'id')->where('department_id', $departmentId),
            ],
            'semester_id' => [
                'required',
                'integer',
                Rule::exists('semesters', 'id')->where('is_active', true),
                Rule::unique('course_assignments')->where(
                    fn ($query) => $query
                        ->where('course_id', $request->input('course_id'))
                        ->where('lecturer_id', $request->input('lecturer_id')),
                ),
            ],
        ]);

        $course = Course::query()->findOrFail($data['course_id']);
        $semester = Semester::query()->findOrFail($data['semester_id']);
        if ($course->semester_type !== null && $course->semester_type !== $semester->type) {
            throw ValidationException::withMessages([
                'semester_id' => 'The course is not offered in this semester type.',
            ]);
        }
        $lecturer = Lecturer::query()->with('user')->findOrFail($data['lecturer_id']);
        if (! $lecturer->user->is_active || ! $course->is_active || ! $semester->is_active) {
            throw ValidationException::withMessages([
                'assignment' => 'An assignment requires an active course, lecturer, and semester.',
            ]);
        }

        CourseAssignment::query()->create($data);

        return to_route('department.courses.index')->with('status', 'Lecturer assigned to course.');
    }

    public function updateAssignment(Request $request, CourseAssignment $assignment): RedirectResponse
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId && (int) $assignment->course?->department_id === (int) $departmentId, 404);

        $data = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('department_id', $departmentId),
            ],
            'lecturer_id' => [
                'required',
                'integer',
                Rule::exists('lecturers', 'id')->where('department_id', $departmentId),
            ],
            'semester_id' => [
                'required',
                'integer',
                Rule::exists('semesters', 'id'),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $course = Course::query()->findOrFail($data['course_id']);
        $lecturer = Lecturer::query()->with('user')->findOrFail($data['lecturer_id']);
        $semester = Semester::query()->findOrFail($data['semester_id']);

        if ($course->semester_type !== null && $course->semester_type !== $semester->type) {
            throw ValidationException::withMessages([
                'semester_id' => 'The course is not offered in this semester type.',
            ]);
        }
        if ($data['is_active'] && (! $course->is_active || ! $lecturer->user->is_active || ! $semester->is_active)) {
            throw ValidationException::withMessages([
                'assignment' => 'An active assignment requires an active course and semester.',
            ]);
        }

        $hasRegistrations = $assignment->course->registrations()
            ->where('semester_id', $assignment->semester_id)
            ->exists();
        $identityChanged = (int) $assignment->course_id !== (int) $data['course_id']
            || (int) $assignment->lecturer_id !== (int) $data['lecturer_id']
            || (int) $assignment->semester_id !== (int) $data['semester_id'];

        if ($hasRegistrations && $identityChanged) {
            throw ValidationException::withMessages([
                'assignment' => 'Assignments linked to registrations cannot be reassigned. Archive the assignment and create a new one.',
            ]);
        }

        $duplicate = CourseAssignment::query()
            ->where('course_id', $data['course_id'])
            ->where('lecturer_id', $data['lecturer_id'])
            ->where('semester_id', $data['semester_id'])
            ->where('id', '!=', $assignment->id)
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'assignment' => 'This lecturer is already assigned to the course in this semester.',
            ]);
        }

        $assignment->update($data);

        return to_route('department.courses.index')->with('status', 'Course assignment updated.');
    }
}
