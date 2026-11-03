<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\CourseRegistration;
use App\Models\Result;
use App\Models\ResultAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultManagementService
{
    public function __construct(private readonly GradeCalculationService $grades) {}

    public function saveDraft(User $actor, CourseRegistration $registration, int $caScore, int $examScore): Result
    {
        $this->assertAssignedLecturer($actor, $registration);
        $grade = $this->grades->calculate($caScore, $examScore);

        return DB::transaction(function () use ($actor, $registration, $caScore, $examScore, $grade): Result {
            $registration = CourseRegistration::query()
                ->with('course', 'semester')
                ->lockForUpdate()
                ->findOrFail($registration->id);

            if ($registration->status !== 'registered') {
                throw ValidationException::withMessages([
                    'registration' => 'Results can only be entered for active course registrations.',
                ]);
            }

            $result = Result::query()
                ->where('course_registration_id', $registration->id)
                ->lockForUpdate()
                ->first();

            if ($result && ! in_array($result->status, [ResultStatus::Draft, ResultStatus::Rejected], true)) {
                throw ValidationException::withMessages([
                    'result' => 'Submitted or approved results cannot be edited.',
                ]);
            }

            $fromStatus = $result?->status;

            if (! $result) {
                $result = new Result(['course_registration_id' => $registration->id]);
            }

            $result->fill([
                ...$grade,
                'ca_score' => $caScore,
                'exam_score' => $examScore,
                'status' => ResultStatus::Draft,
                'latest_feedback' => null,
                'submitted_at' => null,
                'approved_at' => null,
                'published_at' => null,
            ]);
            $result->save();

            $this->audit(
                $result,
                $actor,
                $fromStatus ? 'draft_updated' : 'draft_created',
                $fromStatus,
                ResultStatus::Draft,
                metadata: [
                    'ca_score' => $caScore,
                    'exam_score' => $examScore,
                    'total_score' => $grade['total_score'],
                    'grade' => $grade['grade'],
                ],
            );

            return $result;
        });
    }

    public function submit(User $actor, Result $result): Result
    {
        return DB::transaction(function () use ($actor, $result): Result {
            $result = $this->lockedResult($result);
            $this->assertAssignedLecturer($actor, $result->courseRegistration);

            if ($result->courseRegistration->status !== 'registered') {
                throw ValidationException::withMessages([
                    'registration' => 'Results can only be submitted for active course registrations.',
                ]);
            }

            if ($result->status !== ResultStatus::Draft) {
                throw ValidationException::withMessages(['result' => 'Only draft results can be submitted.']);
            }

            $result->update([
                'status' => ResultStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $this->audit($result, $actor, 'submitted', ResultStatus::Draft, ResultStatus::Submitted);

            return $result->refresh();
        });
    }

    public function approve(User $actor, Result $result): Result
    {
        return $this->review($actor, $result, ResultStatus::Approved, 'approved');
    }

    public function reject(User $actor, Result $result, string $feedback): Result
    {
        if (trim($feedback) === '') {
            throw ValidationException::withMessages(['feedback' => 'Feedback is required when rejecting a result.']);
        }

        return $this->review($actor, $result, ResultStatus::Rejected, 'rejected', trim($feedback));
    }

    public function publish(User $actor, Result $result): Result
    {
        return DB::transaction(function () use ($actor, $result): Result {
            $result = $this->lockedResult($result);
            $this->assertDepartmentAdministrator($actor, $result);

            if ($result->status !== ResultStatus::Approved) {
                throw ValidationException::withMessages(['result' => 'Only approved results can be published.']);
            }

            $result->update([
                'status' => ResultStatus::Published,
                'published_at' => now(),
            ]);

            $this->audit($result, $actor, 'published', ResultStatus::Approved, ResultStatus::Published);

            return $result->refresh();
        });
    }

    public function requestCorrection(User $actor, Result $result, string $reason): Result
    {
        return DB::transaction(function () use ($actor, $result, $reason): Result {
            $result = $this->lockedResult($result);
            $this->assertDepartmentAdministrator($actor, $result);

            if ($result->status !== ResultStatus::Published) {
                throw ValidationException::withMessages([
                    'result' => 'Only published results can be reopened for correction.',
                ]);
            }

            $previous = [
                'ca_score' => $result->ca_score,
                'exam_score' => $result->exam_score,
                'total_score' => $result->total_score,
                'grade' => $result->grade,
                'grade_point' => $result->grade_point,
            ];
            $result->update([
                'status' => ResultStatus::Draft,
                'latest_feedback' => trim($reason),
                'submitted_at' => null,
                'approved_at' => null,
                'published_at' => null,
            ]);

            $this->audit(
                $result,
                $actor,
                'correction_requested',
                ResultStatus::Published,
                ResultStatus::Draft,
                trim($reason),
                $previous,
            );

            return $result->refresh();
        });
    }

    private function review(
        User $actor,
        Result $result,
        ResultStatus $toStatus,
        string $action,
        ?string $feedback = null,
    ): Result {
        return DB::transaction(function () use ($actor, $result, $toStatus, $action, $feedback): Result {
            $result = $this->lockedResult($result);
            $this->assertDepartmentAdministrator($actor, $result);

            if ($result->status !== ResultStatus::Submitted) {
                throw ValidationException::withMessages(['result' => 'Only submitted results can be reviewed.']);
            }

            $result->update([
                'status' => $toStatus,
                'latest_feedback' => $feedback,
                'approved_at' => $toStatus === ResultStatus::Approved ? now() : null,
            ]);

            $this->audit($result, $actor, $action, ResultStatus::Submitted, $toStatus, $feedback);

            return $result->refresh();
        });
    }

    private function lockedResult(Result $result): Result
    {
        return Result::query()
            ->with('courseRegistration.course', 'courseRegistration.semester')
            ->lockForUpdate()
            ->findOrFail($result->id);
    }

    private function assertAssignedLecturer(User $actor, CourseRegistration $registration): void
    {
        $isAssigned = $actor->hasRole(UserRole::Lecturer->value)
            && $actor->lecturer()
                ->whereHas('courseAssignments', fn ($query) => $query
                    ->where('course_id', $registration->course_id)
                    ->where('semester_id', $registration->semester_id))
                ->exists();

        abort_unless($isAssigned, 403);
    }

    private function assertDepartmentAdministrator(User $actor, Result $result): void
    {
        $isDepartmentAdmin = $actor->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $actor->department_id === (int) $result->courseRegistration->course->department_id;

        abort_unless($isDepartmentAdmin, 403);
    }

    /**
     * @param  array<string, int|float|string>|null  $metadata
     */
    private function audit(
        Result $result,
        User $actor,
        string $action,
        ?ResultStatus $fromStatus,
        ResultStatus $toStatus,
        ?string $feedback = null,
        ?array $metadata = null,
    ): void {
        ResultAuditLog::query()->create([
            'result_id' => $result->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'feedback' => $feedback,
            'metadata' => $metadata,
        ]);
    }
}
