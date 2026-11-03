<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view-own-profile',
            'register-courses',
            'view-own-results',
            'enter-assigned-results',
            'submit-assigned-results',
            'review-department-results',
            'manage-department',
            'manage-faculties',
            'manage-all-users',
            'view-university-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $student = Role::findOrCreate(UserRole::Student->value, 'web');
        $student->syncPermissions(['view-own-profile', 'register-courses', 'view-own-results']);

        $lecturer = Role::findOrCreate(UserRole::Lecturer->value, 'web');
        $lecturer->syncPermissions([
            'view-own-profile',
            'enter-assigned-results',
            'submit-assigned-results',
        ]);

        $departmentAdmin = Role::findOrCreate(UserRole::DepartmentAdmin->value, 'web');
        $departmentAdmin->syncPermissions([
            'view-own-profile',
            'review-department-results',
            'manage-department',
        ]);

        $superAdmin = Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $superAdmin->syncPermissions([
            'view-own-profile',
            'manage-faculties',
            'manage-all-users',
            'view-university-reports',
        ]);
    }
}
