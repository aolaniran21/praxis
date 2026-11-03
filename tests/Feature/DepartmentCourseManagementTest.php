<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Services\CourseRegistrationService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets department administrators create courses only for their own department', function () {
    $fixture = makeAcademicFixture();
    $administrator = User::factory()->create(['department_id' => $fixture['department']->id]);
    $administrator->assignRole(UserRole::DepartmentAdmin->value);

    $this->actingAs($administrator)
        ->post(route('department.courses.store'), [
            'code' => 'CSC103',
            'title' => 'Programming Fundamentals',
            'credit_units' => 3,
            'level' => 100,
            'semester_type' => 'first',
        ])
        ->assertRedirect(route('department.courses.index'));

    $this->assertDatabaseHas('courses', [
        'department_id' => $fixture['department']->id,
        'code' => 'CSC103',
    ]);
});

it('does not allow course changes that would alter registered academic records', function () {
    $fixture = makeAcademicFixture();
    $administrator = User::factory()->create(['department_id' => $fixture['department']->id]);
    $administrator->assignRole(UserRole::DepartmentAdmin->value);

    app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id]);

    $this->actingAs($administrator)
        ->from(route('department.courses.index'))
        ->patch(route('department.courses.update', $fixture['course']), [
            'code' => $fixture['course']->code,
            'title' => $fixture['course']->title,
            'credit_units' => 4,
            'level' => $fixture['course']->level,
            'semester_type' => $fixture['course']->semester_type->value,
            'prerequisites' => [],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('course');

    expect(Course::findOrFail($fixture['course']->id)->credit_units)->toBe(3);
});

it('hides courses outside the administrators department', function () {
    $fixture = makeAcademicFixture();
    $otherFaculty = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART']);
    $otherDepartment = Department::create([
        'faculty_id' => $otherFaculty->id,
        'name' => 'History',
        'code' => 'HIS',
    ]);
    $otherCourse = Course::create([
        'department_id' => $otherDepartment->id,
        'code' => 'HIS101',
        'title' => 'Introduction to History',
        'credit_units' => 3,
        'level' => 100,
        'is_active' => true,
    ]);
    $administrator = User::factory()->create(['department_id' => $fixture['department']->id]);
    $administrator->assignRole(UserRole::DepartmentAdmin->value);

    $this->actingAs($administrator)
        ->patch(route('department.courses.update', $otherCourse), [
            'code' => 'HIS101',
            'title' => 'Introduction to History',
            'credit_units' => 3,
            'level' => 100,
            'is_active' => true,
        ])
        ->assertNotFound();
});
