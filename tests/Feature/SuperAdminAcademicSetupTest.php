<?php

use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\CourseAssignment;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Result;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('allows super administrators to create departments within a faculty', function () {
    $faculty = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART']);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->post(route('super-admin.academic.departments.store'), [
            'faculty_id' => $faculty->id,
            'name' => 'History',
            'code' => 'HIS',
        ])
        ->assertRedirect(route('super-admin.academic.index'));

    $this->assertDatabaseHas('departments', [
        'faculty_id' => $faculty->id,
        'name' => 'History',
        'code' => 'HIS',
    ]);
});

it('renders academic setup with editable records for super administrators', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->get(route('super-admin.academic.index'))
        ->assertOk()
        ->assertSee('Edit or archive academic records');
});

it('searches and paginates super-admin academic records', function () {
    $faculties = Faculty::factory()->count(11)->create();
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->get(route('super-admin.academic.index', ['faculty_page' => 2]))
        ->assertOk()
        ->assertViewHas('facultyRecords', fn ($records) => $records->currentPage() === 2
            && $records->count() === 1
            && $records->total() === 11);

    $this->get(route('super-admin.academic.index', ['search' => strtolower($faculties[0]->name)]))
        ->assertOk()
        ->assertViewHas('facultyRecords', fn ($records) => $records->total() === 1);
});

it('rejects course assignments across department boundaries', function () {
    $fixture = makeAcademicFixture();
    $otherFaculty = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART']);
    $otherDepartment = Department::create([
        'faculty_id' => $otherFaculty->id,
        'name' => 'History',
        'code' => 'HIS',
    ]);
    $lecturerUser = User::factory()->create();
    $lecturerUser->assignRole(UserRole::Lecturer->value);
    $lecturer = Lecturer::create([
        'user_id' => $lecturerUser->id,
        'department_id' => $otherDepartment->id,
        'staff_number' => 'STAFF-ART-1',
    ]);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->from(route('super-admin.academic.index'))
        ->post(route('super-admin.academic.assignments.store'), [
            'course_id' => $fixture['course']->id,
            'lecturer_id' => $lecturer->id,
            'semester_id' => $fixture['semester']->id,
        ])
        ->assertSessionHasErrors('lecturer_id');

    expect(CourseAssignment::query()->count())->toBe(0);
});

it('allows super administrators to update and archive academic records without deleting them', function () {
    $fixture = makeAcademicFixture();
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->patch(route('super-admin.academic.update', [
            'entity' => 'courses',
            'record' => $fixture['course']->id,
        ]), [
            'department_id' => $fixture['department']->id,
            'code' => $fixture['course']->code,
            'title' => 'Archived course',
            'credit_units' => $fixture['course']->credit_units,
            'level' => $fixture['course']->level,
            'semester_type' => $fixture['course']->semester_type->value,
            'prerequisites' => [],
            'is_active' => 0,
        ])
        ->assertRedirect(route('super-admin.academic.index'));

    $this->assertDatabaseHas('courses', [
        'id' => $fixture['course']->id,
        'title' => 'Archived course',
        'is_active' => false,
    ]);
});

it('moves the active session and clears the previous current semester together', function () {
    $fixture = makeAcademicFixture();
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->post(route('super-admin.academic.sessions.store'), [
            'name' => '2027/2028',
            'starts_on' => '2027-09-01',
            'ends_on' => '2028-08-31',
            'is_active' => true,
        ])
        ->assertRedirect(route('super-admin.academic.index'));

    expect($fixture['session']->fresh()->is_active)->toBeFalse()
        ->and($fixture['semester']->fresh()->is_current)->toBeFalse()
        ->and(AcademicSession::query()->where('is_active', true)->count())->toBe(1);
});

it('prevents moving departments or programs after dependent profiles exist', function () {
    $fixture = makeAcademicFixture();
    $otherFaculty = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART']);
    $otherDepartment = Department::create([
        'faculty_id' => $otherFaculty->id,
        'name' => 'History',
        'code' => 'HIS',
    ]);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->from(route('super-admin.academic.index'))
        ->patch(route('super-admin.academic.update', [
            'entity' => 'programs',
            'record' => $fixture['program']->id,
        ]), [
            'department_id' => $otherDepartment->id,
            'name' => $fixture['program']->name,
            'code' => $fixture['program']->code,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('department_id');

    $this->patch(route('super-admin.academic.update', [
        'entity' => 'departments',
        'record' => $fixture['department']->id,
    ]), [
        'faculty_id' => $otherFaculty->id,
        'name' => $fixture['department']->name,
        'code' => $fixture['department']->code,
        'is_active' => true,
    ])->assertSessionHasErrors('faculty_id');
});

it('prevents changing semester dates or assignments after registrations exist', function () {
    $fixture = makeAcademicFixture();
    $lecturerUser = User::factory()->create();
    $lecturer = Lecturer::create([
        'user_id' => $lecturerUser->id,
        'department_id' => $fixture['department']->id,
        'staff_number' => 'STAFF-001',
    ]);
    $assignment = CourseAssignment::create([
        'course_id' => $fixture['course']->id,
        'lecturer_id' => $lecturer->id,
        'semester_id' => $fixture['semester']->id,
        'is_active' => true,
    ]);
    CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $fixture['course']->id,
        'semester_id' => $fixture['semester']->id,
        'status' => 'registered',
    ]);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->from(route('super-admin.academic.index'))
        ->patch(route('super-admin.academic.update', [
            'entity' => 'semesters',
            'record' => $fixture['semester']->id,
        ]), [
            'academic_session_id' => $fixture['session']->id,
            'type' => $fixture['semester']->type->value,
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => $fixture['semester']->ends_on->toDateString(),
            'registration_opens_at' => $fixture['semester']->registration_opens_at->toDateTimeString(),
            'registration_closes_at' => $fixture['semester']->registration_closes_at->toDateTimeString(),
            'max_credit_units' => $fixture['semester']->max_credit_units,
            'is_current' => true,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('starts_on');

    $otherLecturerUser = User::factory()->create();
    $otherLecturer = Lecturer::create([
        'user_id' => $otherLecturerUser->id,
        'department_id' => $fixture['department']->id,
        'staff_number' => 'STAFF-002',
    ]);

    $this->patch(route('super-admin.academic.update', [
        'entity' => 'assignments',
        'record' => $assignment->id,
    ]), [
        'course_id' => $fixture['course']->id,
        'lecturer_id' => $otherLecturer->id,
        'semester_id' => $fixture['semester']->id,
        'is_active' => true,
    ])->assertSessionHasErrors('assignment');
});

it('provides repeatable factories for transactional records', function () {
    $registration = CourseRegistration::factory()->create();
    $result = Result::factory()->for($registration)->create();

    expect($registration->student)->not->toBeNull()
        ->and($registration->course)->not->toBeNull()
        ->and($registration->semester)->not->toBeNull()
        ->and($result->total_score)->toBe($result->ca_score + $result->exam_score);
});
