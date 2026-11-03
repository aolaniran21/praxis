<?php

use App\Enums\UserRole;
use App\Models\CourseAssignment;
use App\Models\Lecturer;
use App\Models\User;
use App\Services\CourseRegistrationService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the student dashboard, registration page, and result statement', function () {
    $fixture = makeAcademicFixture();

    $this->actingAs($fixture['studentUser'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Registered courses');

    $this->get(route('student.registrations.index'))
        ->assertOk()
        ->assertSee('CSC101');

    $this->get(route('student.results.index'))
        ->assertOk()
        ->assertSee('Academic result statement');
});

it('renders the department and super-admin management pages', function () {
    $fixture = makeAcademicFixture();
    $departmentAdmin = User::factory()->create(['department_id' => $fixture['department']->id]);
    $departmentAdmin->assignRole(UserRole::DepartmentAdmin->value);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($departmentAdmin)
        ->get(route('department.courses.index'))
        ->assertOk()
        ->assertSee('Department course management');

    $this->actingAs($superAdmin)
        ->get(route('super-admin.academic.index'))
        ->assertOk()
        ->assertSee('Academic setup');

    $this->get(route('super-admin.users.index'))
        ->assertOk()
        ->assertSee('User administration');
});

it('renders assigned course rosters for lecturers', function () {
    $fixture = makeAcademicFixture();
    $lecturerUser = User::factory()->create();
    $lecturerUser->assignRole(UserRole::Lecturer->value);
    $lecturer = Lecturer::create([
        'user_id' => $lecturerUser->id,
        'department_id' => $fixture['department']->id,
        'staff_number' => 'STAFF-001',
    ]);
    $assignment = CourseAssignment::create([
        'course_id' => $fixture['course']->id,
        'lecturer_id' => $lecturer->id,
        'semester_id' => $fixture['semester']->id,
    ]);
    app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id]);

    $this->actingAs($lecturerUser)
        ->get(route('lecturer.courses.index'))
        ->assertOk()
        ->assertSee('CSC101');

    $this->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee($fixture['student']->student_number);
});
