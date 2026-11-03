<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\CourseRegistrationAuditLog;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRegistrationService
{
    /**
     * @param  array<int, int>  $courseIds
     * @return EloquentCollection<int, CourseRegistration>
     */
    public function register(Student $student, array $courseIds): EloquentCollection
    {
        if ($courseIds === [] || count(array_unique($courseIds)) !== count($courseIds)) {
            throw ValidationException::withMessages([
                'courses' => 'Select one or more distinct courses to register.',
            ]);
        }

        return DB::transaction(function () use ($student, $courseIds): EloquentCollection {
            $student = Student::query()
                ->with('program.department')
                ->lockForUpdate()
                ->findOrFail($student->id);

            if (! $student->is_active || ! $student->program->is_active || ! $student->program->department->is_active) {
                throw ValidationException::withMessages([
                    'courses' => 'Your student account is not active.',
                ]);
            }

            $semester = Semester::query()
                ->with('academicSession')
                ->current()
                ->whereHas('academicSession', fn ($query) => $query->where('is_active', true))
                ->lockForUpdate()
                ->first();

            if (! $semester
                || ! $semester->academicSession->is_active
                || ! $semester->registrationIsOpen()) {
                throw ValidationException::withMessages([
                    'courses' => 'Course registration is not open for the current semester.',
                ]);
            }

            $courses = Course::query()
                ->with('prerequisites')
                ->whereIn('id', $courseIds)
                ->where('is_active', true)
                ->whereHas('department', fn ($query) => $query->where('is_active', true))
                ->get()
                ->keyBy('id');

            if ($courses->count() !== count($courseIds)) {
                throw ValidationException::withMessages([
                    'courses' => 'One or more selected courses are unavailable.',
                ]);
            }

            $existingCreditUnits = CourseRegistration::query()
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->where('status', 'registered')
                ->with('course:id,credit_units')
                ->get()
                ->sum(fn (CourseRegistration $registration): int => $registration->course->credit_units);

            $newCreditUnits = 0;

            foreach ($courses as $course) {
                $this->validateEligibility($student, $course, $semester);
                $newCreditUnits += $course->credit_units;
            }

            if ($existingCreditUnits + $newCreditUnits > $semester->max_credit_units) {
                throw ValidationException::withMessages([
                    'courses' => "This registration would exceed the {$semester->max_credit_units}-unit semester limit.",
                ]);
            }

            $registrationIds = [];

            foreach ($courses as $course) {
                $registration = CourseRegistration::query()
                    ->where('student_id', $student->id)
                    ->where('course_id', $course->id)
                    ->where('semester_id', $semester->id)
                    ->lockForUpdate()
                    ->first();

                if ($registration && $registration->status !== 'dropped') {
                    throw ValidationException::withMessages([
                        'courses' => "You are already registered for {$course->code}.",
                    ]);
                }

                $fromStatus = $registration?->status;
                $registration ??= new CourseRegistration([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'semester_id' => $semester->id,
                ]);
                $registration->forceFill([
                    'status' => 'registered',
                    'dropped_at' => null,
                ])->save();
                CourseRegistrationAuditLog::query()->create([
                    'course_registration_id' => $registration->id,
                    'actor_id' => $student->user_id,
                    'action' => $fromStatus === 'dropped' ? 'reinstated' : 'registered',
                    'from_status' => $fromStatus,
                    'to_status' => 'registered',
                ]);
                $registrationIds[] = $registration->id;
            }

            return CourseRegistration::query()
                ->with('course', 'semester.academicSession')
                ->whereKey($registrationIds)
                ->get();
        });
    }

    public function drop(User $actor, CourseRegistration $registration): void
    {
        DB::transaction(function () use ($actor, $registration): void {
            $registration = CourseRegistration::query()
                ->with('semester.academicSession', 'result')
                ->lockForUpdate()
                ->findOrFail($registration->id);

            $student = $actor->student;
            abort_unless(
                $actor->hasRole(UserRole::Student->value)
                    && $actor->is_active
                    && $student
                    && (int) $student->id === (int) $registration->student_id,
                403,
            );

            $semester = $registration->semester;
            if ($registration->status !== 'registered'
                || ! $semester->is_active
                || ! $semester->is_current
                || ! $semester->academicSession->is_active
                || ! $semester->registration_opens_at?->isPast()
                || ! $semester->registration_closes_at?->isFuture()
                || $registration->result) {
                throw ValidationException::withMessages([
                    'registration' => 'This course can no longer be dropped. Drops are available only before the registration deadline and before result entry.',
                ]);
            }

            $registration->update([
                'status' => 'dropped',
                'dropped_at' => now(),
            ]);

            CourseRegistrationAuditLog::query()->create([
                'course_registration_id' => $registration->id,
                'actor_id' => $actor->id,
                'action' => 'dropped',
                'from_status' => 'registered',
                'to_status' => 'dropped',
            ]);
        });
    }

    private function validateEligibility(Student $student, Course $course, Semester $semester): void
    {
        if (! $student->program->is_active
            || ! $student->program->department->is_active
            || (int) $course->department_id !== (int) $student->program->department_id) {
            throw ValidationException::withMessages([
                'courses' => "{$course->code} is outside your program's department.",
            ]);
        }

        if ($course->level !== $student->level) {
            throw ValidationException::withMessages([
                'courses' => "{$course->code} is not available at your current level.",
            ]);
        }

        if ($course->semester_type !== null && $course->semester_type !== $semester->type) {
            throw ValidationException::withMessages([
                'courses' => "{$course->code} is not offered in this semester.",
            ]);
        }

        foreach ($course->prerequisites as $prerequisite) {
            $hasPassed = $student->courseRegistrations()
                ->where('course_id', $prerequisite->id)
                ->whereHas('result', fn ($query) => $query
                    ->where('status', ResultStatus::Published->value)
                    ->where('total_score', '>=', 40))
                ->exists();

            if (! $hasPassed) {
                throw ValidationException::withMessages([
                    'courses' => "You must pass {$prerequisite->code} before registering for {$course->code}.",
                ]);
            }
        }
    }
}
