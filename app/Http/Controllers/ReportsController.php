<?php

namespace App\Http\Controllers;

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use App\Models\Result;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertDepartmentScope($request);
        $filters = $this->validatedFilters($request);
        $query = $this->reportResults($request, $filters);

        return view('reports.index', [
            'results' => (clone $query)
                ->orderByDesc('updated_at')
                ->paginate(50)
                ->withQueryString(),
            'totalResults' => (clone $query)->count(),
            'passCount' => (clone $query)->where('total_score', '>=', 40)->count(),
            'averageScore' => (clone $query)->avg('total_score') ?? 0,
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
            'semesters' => Semester::query()->with('academicSession')->orderByDesc('starts_on')->get(),
            'departments' => $request->user()->hasRole(UserRole::SuperAdmin->value)
                ? Department::query()->orderBy('name')->get()
                : Department::query()->whereKey($request->user()->department_id)->get(),
            'programs' => Program::query()
                ->when(! $request->user()->hasRole(UserRole::SuperAdmin->value), fn (Builder $query) => $query
                    ->whereHas('department', fn (Builder $query) => $query
                        ->whereKey($request->user()->department_id)))
                ->orderBy('name')
                ->get(),
            'courses' => Course::query()
                ->when(! $request->user()->hasRole(UserRole::SuperAdmin->value), fn (Builder $query) => $query
                    ->where('department_id', $request->user()->department_id))
                ->orderBy('code')
                ->get(),
            'semesterGpas' => ($filters['semester_id']
                && ! $filters['course_id']
                && $filters['status'] === ResultStatus::Published->value)
                ? $this->semesterGpas($request, $filters)
                : collect(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->assertDepartmentScope($request);
        $filters = $this->validatedFilters($request);
        $query = $this->reportResults($request, $filters);

        return ResponseFactory::streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Student number',
                'Student name',
                'Department',
                'Program',
                'Level',
                'Academic session',
                'Semester',
                'Course code',
                'Course title',
                'Credits',
                'Score',
                'Grade',
                'Grade point',
                'Result status',
                'Published at',
            ]);

            $query->chunkById(500, function ($results) use ($output): void {
                foreach ($results as $result) {
                    $registration = $result->courseRegistration;
                    $student = $registration->student;
                    $course = $registration->course;
                    $semester = $registration->semester;

                    fputcsv($output, [
                        $this->csvCell($student->student_number),
                        $this->csvCell($student->user->name),
                        $this->csvCell($course->department->name),
                        $this->csvCell($student->program->name),
                        $student->level,
                        $this->csvCell($semester->academicSession->name),
                        $this->csvCell($semester->type->value),
                        $this->csvCell($course->code),
                        $this->csvCell($course->title),
                        $course->credit_units,
                        $result->total_score,
                        $result->grade,
                        $result->grade_point,
                        $result->status->value,
                        $result->published_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($output);
        }, 'published-results.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{academic_session_id: int|null, semester_id: int|null, department_id: int|null, program_id: int|null, level: int|null, course_id: int|null, status: string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'academic_session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'level' => ['nullable', 'integer', 'in:100,200,300,400,500,600,700,800,900'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'status' => ['nullable', 'string', Rule::enum(ResultStatus::class)],
        ]);

        return array_merge([
            'academic_session_id' => null,
            'semester_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'course_id' => null,
            'status' => ResultStatus::Published->value,
        ], array_filter($validated, fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * @param  array{academic_session_id: int|null, semester_id: int|null, department_id: int|null, program_id: int|null, level: int|null, course_id: int|null, status: string}  $filters
     * @return Builder<Result>
     */
    private function reportResults(Request $request, array $filters): Builder
    {
        $query = Result::query()
            ->with([
                'courseRegistration.student.user',
                'courseRegistration.course.department',
                'courseRegistration.semester.academicSession',
            ])
            ->where('status', $filters['status'])
            ->when($filters['academic_session_id'] ?? null, fn (Builder $query, int $sessionId) => $query
                ->whereHas('courseRegistration.semester', fn (Builder $query) => $query
                    ->where('academic_session_id', $sessionId)))
            ->when($filters['semester_id'] ?? null, fn (Builder $query, int $semesterId) => $query
                ->whereHas('courseRegistration', fn (Builder $query) => $query
                    ->where('semester_id', $semesterId)))
            ->when($filters['course_id'] ?? null, fn (Builder $query, int $courseId) => $query
                ->whereHas('courseRegistration', fn (Builder $query) => $query
                    ->where('course_id', $courseId)))
            ->when($filters['program_id'] ?? null, fn (Builder $query, int $programId) => $query
                ->whereHas('courseRegistration.student', fn (Builder $query) => $query
                    ->where('program_id', $programId)))
            ->when($filters['level'] ?? null, fn (Builder $query, int $level) => $query
                ->whereHas('courseRegistration.student', fn (Builder $query) => $query
                    ->where('level', $level)));

        if ($request->user()->hasRole(UserRole::DepartmentAdmin->value)
            && ! $request->user()->hasRole(UserRole::SuperAdmin->value)) {
            return $query->whereHas('courseRegistration.course', fn (Builder $query) => $query
                ->where('department_id', $request->user()->department_id));
        }

        return $query
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $departmentId) => $query
                ->whereHas('courseRegistration.course', fn (Builder $query) => $query
                    ->where('department_id', $departmentId)))
            ->when($filters['program_id'] ?? null, fn (Builder $query, int $programId) => $query
                ->whereHas('courseRegistration.student', fn (Builder $query) => $query
                    ->where('program_id', $programId)));
    }

    /**
     * @param  array{academic_session_id: int|null, semester_id: int|null, department_id: int|null, program_id: int|null, level: int|null, course_id: int|null, status: string}  $filters
     */
    private function semesterGpas(Request $request, array $filters): Collection
    {
        return DB::table('results')
            ->join('course_registrations', 'course_registrations.id', '=', 'results.course_registration_id')
            ->join('courses', 'courses.id', '=', 'course_registrations.course_id')
            ->join('students', 'students.id', '=', 'course_registrations.student_id')
            ->join('programs', 'programs.id', '=', 'students.program_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('results.status', ResultStatus::Published->value)
            ->where('course_registrations.semester_id', $filters['semester_id'])
            ->when($filters['department_id'], fn (QueryBuilder $query, int $departmentId) => $query
                ->where('courses.department_id', $departmentId))
            ->when($filters['program_id'], fn (QueryBuilder $query, int $programId) => $query
                ->where('students.program_id', $programId))
            ->when($filters['level'], fn (QueryBuilder $query, int $level) => $query
                ->where('students.level', $level))
            ->when($request->user()->hasRole(UserRole::DepartmentAdmin->value)
                && ! $request->user()->hasRole(UserRole::SuperAdmin->value),
                fn (QueryBuilder $query) => $query->where('courses.department_id', $request->user()->department_id))
            ->select([
                'students.student_number',
                'users.name as student_name',
                'programs.name as program_name',
            ])
            ->selectRaw('ROUND(SUM(results.grade_point * courses.credit_units) / NULLIF(SUM(courses.credit_units), 0), 2) as gpa')
            ->selectRaw('SUM(courses.credit_units) as credit_units')
            ->groupBy('students.id', 'students.student_number', 'users.name', 'programs.name')
            ->orderBy('students.student_number')
            ->get();
    }

    private function assertDepartmentScope(Request $request): void
    {
        abort_if(
            $request->user()->hasRole(UserRole::DepartmentAdmin->value)
                && ! $request->user()->hasRole(UserRole::SuperAdmin->value)
                && ! $request->user()->department_id,
            403,
            'A department has not been assigned to this account.',
        );
    }

    private function csvCell(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
