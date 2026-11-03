<?php

namespace App\Policies;

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function view(User $user, Result $result): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        $registration = $result->courseRegistration;

        if ($user->hasRole(UserRole::Student->value)) {
            return $result->status === ResultStatus::Published
                && ($user->student?->is($registration->student) ?? false);
        }

        if ($user->hasRole(UserRole::Lecturer->value)) {
            return $registration->status === 'registered'
                && $user->lecturer()
                    ->whereHas('courseAssignments', fn ($query) => $query
                        ->where('course_id', $registration->course_id)
                        ->where('semester_id', $registration->semester_id)
                        ->where('is_active', true))
                    ->exists();
        }

        return $user->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $user->department_id === (int) $registration->course->department_id;
    }

    public function update(User $user, Result $result): bool
    {
        return $result->courseRegistration->status === 'registered'
            && in_array($result->status, [ResultStatus::Draft, ResultStatus::Rejected], true)
            && $user->hasRole(UserRole::Lecturer->value)
            && $user->lecturer()
                ->whereHas('courseAssignments', fn ($query) => $query
                    ->where('course_id', $result->courseRegistration->course_id)
                    ->where('semester_id', $result->courseRegistration->semester_id)
                    ->where('is_active', true))
                ->exists();
    }

    public function submit(User $user, Result $result): bool
    {
        return $result->courseRegistration->status === 'registered'
            && $result->status === ResultStatus::Draft
            && $user->hasRole(UserRole::Lecturer->value)
            && $user->lecturer()
                ->whereHas('courseAssignments', fn ($query) => $query
                    ->where('course_id', $result->courseRegistration->course_id)
                    ->where('semester_id', $result->courseRegistration->semester_id)
                    ->where('is_active', true))
                ->exists();
    }

    public function review(User $user, Result $result): bool
    {
        return $result->status === ResultStatus::Submitted
            && $user->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $user->department_id === (int) $result->courseRegistration->course->department_id;
    }

    public function publish(User $user, Result $result): bool
    {
        return $result->status === ResultStatus::Approved
            && $user->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $user->department_id === (int) $result->courseRegistration->course->department_id;
    }

    public function requestCorrection(User $user, Result $result): bool
    {
        return $result->status === ResultStatus::Published
            && $user->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $user->department_id === (int) $result->courseRegistration->course->department_id;
    }
}
