<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CourseRegistration;
use App\Models\User;

class CourseRegistrationPolicy
{
    public function view(User $user, CourseRegistration $registration): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::Student->value)) {
            return $user->student?->is($registration->student) ?? false;
        }

        if ($user->hasRole(UserRole::Lecturer->value)) {
            return $user->lecturer()
                ->whereHas('courseAssignments', fn ($query) => $query
                    ->where('course_id', $registration->course_id)
                    ->where('semester_id', $registration->semester_id))
                ->exists();
        }

        return $user->hasRole(UserRole::DepartmentAdmin->value)
            && (int) $user->department_id === (int) $registration->course?->department_id;
    }

    public function drop(User $user, CourseRegistration $registration): bool
    {
        return $user->hasRole(UserRole::Student->value)
            && $user->is_active
            && $user->student?->is($registration->student) === true
            && $registration->status === 'registered';
    }
}
