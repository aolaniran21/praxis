<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('creates a student account, profile, and role atomically', function () {
    $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI']);
    $department = Department::create([
        'faculty_id' => $faculty->id,
        'name' => 'Computer Science',
        'code' => 'CSC',
    ]);
    $program = Program::create([
        'department_id' => $department->id,
        'name' => 'Computer Science',
        'code' => 'BSC-CS',
    ]);
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $response = $this->actingAs($admin)->post(route('super-admin.users.store'), [
        'name' => 'New Student',
        'email' => 'new.student@example.test',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'role' => UserRole::Student->value,
        'program_id' => $program->id,
        'student_number' => 'STU-100',
        'level' => 100,
    ]);

    $response->assertRedirect(route('super-admin.users.index'));
    $user = User::query()->where('email', 'new.student@example.test')->firstOrFail();
    expect($user->hasRole(UserRole::Student->value))->toBeTrue();
    $this->assertDatabaseHas('students', [
        'user_id' => $user->id,
        'program_id' => $program->id,
        'student_number' => 'STU-100',
        'level' => 100,
    ]);
});

it('denies non-super-admins access to user administration', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::Student->value);

    $this->actingAs($student)
        ->get(route('super-admin.users.index'))
        ->assertForbidden();
});

it('creates the initial super administrator through a guarded console bootstrap', function () {
    $this->artisan('app:create-initial-super-admin')
        ->expectsQuestion('Administrator name', 'Initial Administrator')
        ->expectsQuestion('Administrator email', 'initial-admin@example.test')
        ->expectsQuestion('Password', 'SecureBootstrap123!')
        ->expectsQuestion('Confirm password', 'SecureBootstrap123!')
        ->expectsOutputToContain('Initial super administrator created.')
        ->assertExitCode(0);

    $user = User::query()->where('email', 'initial-admin@example.test')->firstOrFail();
    expect($user->hasRole(UserRole::SuperAdmin->value))->toBeTrue();

    $this->artisan('app:create-initial-super-admin')
        ->expectsOutputToContain('A super administrator already exists; bootstrap is disabled.')
        ->assertExitCode(1);
});

it('renders searchable and paginated user administration for super administrators', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->get(route('super-admin.users.index'))
        ->assertOk()
        ->assertSee('Edit / archive account')
        ->assertSee('Search accounts');
});

it('does not allow inactive accounts to authenticate', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('logs out an already authenticated account as soon as it is deactivated', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('lets super administrators update student profiles and archive accounts', function () {
    $fixture = makeAcademicFixture();
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    $this->actingAs($admin)
        ->patch(route('super-admin.users.update', $fixture['studentUser']), [
            'name' => 'Updated Student',
            'email' => 'updated.student@example.test',
            'is_active' => 0,
            'program_id' => $fixture['program']->id,
            'student_number' => 'STU-001-UPDATED',
            'level' => 200,
        ])
        ->assertRedirect(route('super-admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $fixture['studentUser']->id,
        'name' => 'Updated Student',
        'email' => 'updated.student@example.test',
        'is_active' => false,
    ]);
    $this->assertDatabaseHas('students', [
        'id' => $fixture['student']->id,
        'student_number' => 'STU-001-UPDATED',
        'level' => 200,
    ]);
});
