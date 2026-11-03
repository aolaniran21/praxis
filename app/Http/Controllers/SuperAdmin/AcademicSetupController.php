<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\SemesterType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicSetupController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $filters['search'] ?? null;
        $term = $search === null ? null : '%'.mb_strtolower($search).'%';

        return view('super-admin.academic.index', [
            'faculties' => Faculty::query()->orderBy('name')->get(),
            'departments' => Department::query()->with('faculty')->orderBy('name')->get(),
            'programs' => Program::query()->with('department')->orderBy('name')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
            'semesters' => Semester::query()->with('academicSession')->orderByDesc('starts_on')->get(),
            'courses' => Course::query()->with('department', 'prerequisites')->orderBy('code')->get(),
            'lecturers' => Lecturer::query()->with('user', 'department')->orderBy('staff_number')->get(),
            'assignments' => CourseAssignment::query()
                ->with('course', 'lecturer.user', 'semester.academicSession')
                ->latest('semester_id')
                ->get(),
            'facultyRecords' => Faculty::query()
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$term])))
                ->orderBy('name')
                ->paginate(10, ['*'], 'faculty_page')
                ->withQueryString(),
            'departmentRecords' => Department::query()
                ->with('faculty')
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                    ->orWhereHas('faculty', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))))
                ->orderBy('name')
                ->paginate(10, ['*'], 'department_page')
                ->withQueryString(),
            'programRecords' => Program::query()
                ->with('department')
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                    ->orWhereHas('department', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))))
                ->orderBy('name')
                ->paginate(10, ['*'], 'program_page')
                ->withQueryString(),
            'sessionRecords' => AcademicSession::query()
                ->when($term, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))
                ->orderByDesc('starts_on')
                ->paginate(10, ['*'], 'session_page')
                ->withQueryString(),
            'semesterRecords' => Semester::query()
                ->with('academicSession')
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(type) LIKE ?', [$term])
                    ->orWhereHas('academicSession', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))))
                ->orderByDesc('starts_on')
                ->paginate(10, ['*'], 'semester_page')
                ->withQueryString(),
            'courseRecords' => Course::query()
                ->with('department', 'prerequisites')
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereHas('department', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))))
                ->orderBy('code')
                ->paginate(10, ['*'], 'course_page')
                ->withQueryString(),
            'assignmentRecords' => CourseAssignment::query()
                ->with('course', 'lecturer.user', 'semester.academicSession')
                ->when($term, fn ($query) => $query->where(fn ($query) => $query
                    ->whereHas('course', fn ($query) => $query
                        ->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$term]))
                    ->orWhereHas('lecturer.user', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('semester.academicSession', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$term]))))
                ->latest('semester_id')
                ->paginate(10, ['*'], 'assignment_page')
                ->withQueryString(),
        ]);
    }

    public function storeFaculty(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:faculties,name'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:faculties,code'],
        ]);
        Faculty::query()->create($data);

        return $this->saved('Faculty created.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:departments,code'],
        ]);
        $exists = Department::query()
            ->where('faculty_id', $data['faculty_id'])
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'This faculty already has a department with that name.']);
        }
        Department::query()->create($data);

        return $this->saved('Department created.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:programs,code'],
        ]);
        $exists = Program::query()
            ->where('department_id', $data['department_id'])
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'This department already has a program with that name.']);
        }
        Program::query()->create($data);

        return $this->saved('Program created.');
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:20', 'unique:academic_sessions,name'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            if ($data['is_active']) {
                AcademicSession::query()->where('is_active', true)->update(['is_active' => false]);
                Semester::query()->where('is_current', true)->update(['is_current' => false]);
            }

            AcademicSession::query()->create($data);
        });

        return $this->saved('Academic session created.');
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'type' => [
                'required',
                Rule::enum(SemesterType::class),
                Rule::unique('semesters', 'type')->where(
                    fn ($query) => $query->where('academic_session_id', $request->input('academic_session_id')),
                ),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
            'max_credit_units' => ['required', 'integer', 'between:1,60'],
            'is_current' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            if ($data['is_current']) {
                $sessionIsActive = AcademicSession::query()
                    ->whereKey($data['academic_session_id'])
                    ->where('is_active', true)
                    ->exists();

                if (! $sessionIsActive) {
                    throw ValidationException::withMessages([
                        'academic_session_id' => 'The current semester must belong to an active academic session.',
                    ]);
                }

                Semester::query()->where('is_current', true)->update(['is_current' => false]);
            }

            Semester::query()->create($data);
        });

        return $this->saved('Semester created.');
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:courses,code'],
            'title' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'between:1,30'],
            'level' => ['required', 'integer', Rule::in([100, 200, 300, 400, 500, 600, 700, 800, 900])],
            'semester_type' => ['nullable', Rule::enum(SemesterType::class)],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'distinct', 'exists:courses,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $prerequisiteIds = $data['prerequisites'] ?? [];
        $crossDepartmentPrerequisite = Course::query()
            ->whereIn('id', $prerequisiteIds)
            ->where('department_id', '!=', $data['department_id'])
            ->exists();
        if ($crossDepartmentPrerequisite) {
            throw ValidationException::withMessages([
                'prerequisites' => 'Course prerequisites must belong to the same department.',
            ]);
        }

        DB::transaction(function () use ($data, $prerequisiteIds): void {
            $course = Course::query()->create(collect($data)->except('prerequisites')->all());
            $course->prerequisites()->sync($prerequisiteIds);
        });

        return $this->saved('Course created.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('is_active', true)],
            'lecturer_id' => [
                'required',
                'integer',
                Rule::exists('lecturers', 'id')->whereIn('user_id', User::query()->select('id')->where('is_active', true)),
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
        $lecturer = Lecturer::query()->findOrFail($data['lecturer_id']);
        $semester = Semester::query()->findOrFail($data['semester_id']);

        if ((int) $course->department_id !== (int) $lecturer->department_id) {
            throw ValidationException::withMessages([
                'lecturer_id' => 'The lecturer must belong to the course department.',
            ]);
        }
        if (! $lecturer->user->is_active) {
            throw ValidationException::withMessages([
                'lecturer_id' => 'The lecturer account is inactive.',
            ]);
        }

        if ($course->semester_type !== null && $course->semester_type !== $semester->type) {
            throw ValidationException::withMessages([
                'semester_id' => 'The course is not offered in this semester type.',
            ]);
        }

        CourseAssignment::query()->create($data);

        return $this->saved('Lecturer assigned to course.');
    }

    public function update(Request $request, string $entity, int $record): RedirectResponse
    {
        $model = match ($entity) {
            'faculties' => Faculty::query()->findOrFail($record),
            'departments' => Department::query()->findOrFail($record),
            'programs' => Program::query()->findOrFail($record),
            'sessions' => AcademicSession::query()->findOrFail($record),
            'semesters' => Semester::query()->findOrFail($record),
            'courses' => Course::query()->findOrFail($record),
            'assignments' => CourseAssignment::query()->findOrFail($record),
            default => abort(404),
        };

        $data = match ($entity) {
            'faculties' => $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('faculties', 'name')->ignore($record)],
                'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('faculties', 'code')->ignore($record)],
                'is_active' => ['required', 'boolean'],
            ]),
            'departments' => $request->validate([
                'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->ignore($record)],
                'is_active' => ['required', 'boolean'],
            ]),
            'programs' => $request->validate([
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('programs', 'code')->ignore($record)],
                'is_active' => ['required', 'boolean'],
            ]),
            'sessions' => $request->validate([
                'name' => ['required', 'string', 'max:20', Rule::unique('academic_sessions', 'name')->ignore($record)],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
                'is_active' => ['required', 'boolean'],
            ]),
            'semesters' => $request->validate([
                'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
                'type' => [
                    'required',
                    Rule::enum(SemesterType::class),
                    Rule::unique('semesters', 'type')
                        ->where(fn ($query) => $query->where('academic_session_id', $request->input('academic_session_id')))
                        ->ignore($record),
                ],
                'starts_on' => ['required', 'date'],
                'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
                'registration_opens_at' => ['nullable', 'date'],
                'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
                'max_credit_units' => ['required', 'integer', 'between:1,60'],
                'is_current' => ['required', 'boolean'],
                'is_active' => ['required', 'boolean'],
            ]),
            'courses' => $request->validate([
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('courses', 'code')->ignore($record)],
                'title' => ['required', 'string', 'max:255'],
                'credit_units' => ['required', 'integer', 'between:1,30'],
                'level' => ['required', 'integer', Rule::in([100, 200, 300, 400, 500, 600, 700, 800, 900])],
                'semester_type' => ['nullable', Rule::enum(SemesterType::class)],
                'prerequisites' => ['nullable', 'array'],
                'prerequisites.*' => ['integer', 'distinct', 'exists:courses,id'],
                'is_active' => ['required', 'boolean'],
            ]),
            'assignments' => $request->validate([
                'course_id' => ['required', 'integer', 'exists:courses,id'],
                'lecturer_id' => ['required', 'integer', 'exists:lecturers,id'],
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'is_active' => ['required', 'boolean'],
            ]),
        };

        if ($entity === 'departments' && Department::query()
            ->where('faculty_id', $data['faculty_id'])
            ->where('name', $data['name'])
            ->where('id', '!=', $record)
            ->exists()) {
            throw ValidationException::withMessages(['name' => 'This faculty already has a department with that name.']);
        }

        if ($entity === 'programs' && Program::query()
            ->where('department_id', $data['department_id'])
            ->where('name', $data['name'])
            ->where('id', '!=', $record)
            ->exists()) {
            throw ValidationException::withMessages(['name' => 'This department already has a program with that name.']);
        }

        if ($entity === 'departments'
            && (int) $model->faculty_id !== (int) $data['faculty_id']
            && ($model->programs()->exists() || $model->courses()->exists() || $model->lecturers()->exists() || $model->administrators()->exists())) {
            throw ValidationException::withMessages([
                'faculty_id' => 'A department with academic records cannot be moved to another faculty.',
            ]);
        }

        if ($entity === 'programs'
            && (int) $model->department_id !== (int) $data['department_id']
            && $model->students()->exists()) {
            throw ValidationException::withMessages([
                'department_id' => 'A program with student profiles cannot be moved to another department.',
            ]);
        }

        if ($entity === 'sessions' && $model->semesters()->whereHas('courseRegistrations')->exists()
            && ($model->starts_on->toDateString() !== $data['starts_on']
                || $model->ends_on->toDateString() !== $data['ends_on'])) {
            throw ValidationException::withMessages([
                'starts_on' => 'Academic session dates cannot change after registrations exist.',
            ]);
        }

        if ($entity === 'semesters' && $model->courseRegistrations()->exists()
            && ((int) $model->academic_session_id !== (int) $data['academic_session_id']
                || $model->type->value !== $data['type']
                || $model->starts_on->toDateString() !== $data['starts_on']
                || $model->ends_on->toDateString() !== $data['ends_on'])) {
            throw ValidationException::withMessages([
                'starts_on' => 'Semester identity and dates cannot change after registrations exist.',
            ]);
        }

        if ($entity === 'courses') {
            $prerequisiteIds = $data['prerequisites'] ?? [];
            $invalidPrerequisites = Course::query()
                ->whereIn('id', $prerequisiteIds)
                ->where(fn ($query) => $query
                    ->where('department_id', '!=', $data['department_id'])
                    ->orWhere('id', $record))
                ->exists();
            if ($invalidPrerequisites) {
                throw ValidationException::withMessages([
                    'prerequisites' => 'Prerequisites must be other courses in the same department.',
                ]);
            }

            if ($model->registrations()->exists()) {
                $changed = $model->department_id !== (int) $data['department_id']
                    || $model->code !== $data['code']
                    || $model->title !== $data['title']
                    || $model->credit_units !== (int) $data['credit_units']
                    || $model->level !== (int) $data['level']
                    || $model->semester_type?->value !== ($data['semester_type'] ?? null)
                    || $model->prerequisites()->pluck('courses.id')->map(fn ($id) => (int) $id)->sort()->values()->all()
                        !== collect($prerequisiteIds)->map(fn ($id) => (int) $id)->sort()->values()->all();
                if ($changed) {
                    throw ValidationException::withMessages([
                        'course' => 'Academic course details cannot change after registrations exist. Archive the course instead.',
                    ]);
                }
            }
        }

        if ($entity === 'departments' && $data['is_active']
            && ! Faculty::query()->whereKey($data['faculty_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['faculty_id' => 'An active department must belong to an active faculty.']);
        }

        if ($entity === 'programs' && $data['is_active']
            && ! Department::query()->whereKey($data['department_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['department_id' => 'An active program must belong to an active department.']);
        }

        if ($entity === 'courses' && $data['is_active']
            && ! Department::query()->whereKey($data['department_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['department_id' => 'An active course must belong to an active department.']);
        }

        if ($entity === 'assignments') {
            $course = Course::query()->findOrFail($data['course_id']);
            $lecturer = Lecturer::query()->findOrFail($data['lecturer_id']);
            $semester = Semester::query()->findOrFail($data['semester_id']);
            if ((int) $course->department_id !== (int) $lecturer->department_id
                || ($course->semester_type !== null && $course->semester_type !== $semester->type)) {
                throw ValidationException::withMessages([
                    'assignment' => 'The lecturer, course, and semester must be compatible.',
                ]);
            }
            if ($data['is_active']
                && (! $course->is_active || ! $lecturer->user->is_active || ! $semester->is_active)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Active assignments require an active course, lecturer, and semester.',
                ]);
            }
            $hasRegistrations = $model->course->registrations()
                ->where('semester_id', $model->semester_id)
                ->exists();
            $identityChanged = (int) $model->course_id !== (int) $data['course_id']
                || (int) $model->lecturer_id !== (int) $data['lecturer_id']
                || (int) $model->semester_id !== (int) $data['semester_id'];
            if ($hasRegistrations && $identityChanged) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignments linked to registrations cannot be reassigned. Archive them instead.',
                ]);
            }
            if ($data['is_active']
                && (! $course->is_active || ! $lecturer->user->is_active || ! $semester->is_active)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Active assignments require an active course, lecturer, and semester.',
                ]);
            }
            $duplicate = CourseAssignment::query()
                ->where('course_id', $data['course_id'])
                ->where('lecturer_id', $data['lecturer_id'])
                ->where('semester_id', $data['semester_id'])
                ->where('id', '!=', $record)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['assignment' => 'This lecturer is already assigned to the course in this semester.']);
            }
        }

        DB::transaction(function () use ($entity, $model, $data, $record): void {
            if ($entity === 'courses') {
                $prerequisites = $data['prerequisites'] ?? [];
                unset($data['prerequisites']);
            }

            if ($entity === 'sessions' && ! $data['is_active']) {
                Semester::query()
                    ->where('academic_session_id', $record)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            if ($entity === 'sessions' && $data['is_active']) {
                AcademicSession::query()->where('is_active', true)->where('id', '!=', $record)->update(['is_active' => false]);
                Semester::query()->where('is_current', true)->update(['is_current' => false]);
            }

            if ($entity === 'semesters' && $data['is_current']) {
                $activeSession = AcademicSession::query()
                    ->whereKey($data['academic_session_id'])
                    ->where('is_active', true)
                    ->exists();
                if (! $activeSession || ! $data['is_active']) {
                    throw ValidationException::withMessages([
                        'academic_session_id' => 'A current semester must be active and belong to the active academic session.',
                    ]);
                }
                Semester::query()->where('is_current', true)->where('id', '!=', $record)->update(['is_current' => false]);
            }

            if ($entity === 'semesters') {
                if (! $data['is_active']) {
                    $data['is_current'] = false;
                }
                if (! $data['is_current']) {
                    $model->update(['is_current' => false]);
                }
            }

            $model->update($data);

            if ($entity === 'courses') {
                $model->prerequisites()->sync($prerequisites);
            }
        });

        return $this->saved(match ($entity) {
            'faculties' => 'Faculty updated.',
            'departments' => 'Department updated.',
            'programs' => 'Program updated.',
            'sessions' => 'Academic session updated.',
            'semesters' => 'Semester updated.',
            'courses' => 'Course updated.',
            'assignments' => 'Course assignment updated.',
        });
    }

    private function saved(string $message): RedirectResponse
    {
        return to_route('super-admin.academic.index')->with('status', $message);
    }
}
