<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CourseAssignment;
use App\Models\User;

class CourseAssignmentPolicy
{
    public function view(User $user, CourseAssignment $assignment): bool
    {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return true;
        }

        if ($user->hasRole(UserRole::Lecturer->value)) {
            return $user->lecturer?->is($assignment->lecturer) ?? false;
        }

        return $user->hasRole(UserRole::DepartmentAdmin->value)
            && $user->department_id === $assignment->course?->department_id;
    }
}
