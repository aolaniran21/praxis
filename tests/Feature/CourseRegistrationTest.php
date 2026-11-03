<?php

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\CourseRegistrationAuditLog;
use App\Models\User;
use App\Policies\CourseRegistrationPolicy;
use App\Services\CourseRegistrationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('registers eligible courses for a student in one transaction', function () {
    $fixture = makeAcademicFixture();

    $registered = app(CourseRegistrationService::class)->register(
        $fixture['student'],
        [$fixture['course']->id],
    );

    expect($registered)->toHaveCount(1)
        ->and($registered->first()->course_id)->toBe($fixture['course']->id)
        ->and(CourseRegistration::query()->count())->toBe(1);
});

it('prevents duplicate registration for the same semester', function () {
    $fixture = makeAcademicFixture();
    $service = app(CourseRegistrationService::class);
    $service->register($fixture['student'], [$fixture['course']->id]);

    expect(fn () => $service->register($fixture['student'], [$fixture['course']->id]))
        ->toThrow(ValidationException::class);
});

it('allows a student to drop and re-add a course before the deadline and retains an audit trail', function () {
    $fixture = makeAcademicFixture();
    $registration = app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id])
        ->firstOrFail();
    expect($fixture['studentUser']->is_active)->toBeTrue()
        ->and($fixture['studentUser']->hasRole('student'))->toBeTrue()
        ->and((new CourseRegistrationPolicy)->drop($fixture['studentUser'], $registration))->toBeTrue();

    $this->actingAs($fixture['studentUser'])
        ->delete(route('student.registrations.drop', $registration))
        ->assertRedirect(route('student.registrations.index'));

    expect($registration->fresh()->status)->toBe('dropped')
        ->and($registration->fresh()->dropped_at)->not->toBeNull();

    app(CourseRegistrationService::class)->register($fixture['student'], [$fixture['course']->id]);

    expect($registration->fresh()->status)->toBe('registered')
        ->and(CourseRegistrationAuditLog::query()->pluck('action')->all())
        ->toBe(['registered', 'dropped', 'reinstated']);
});

it('blocks course drops after the registration deadline', function () {
    $fixture = makeAcademicFixture();
    $registration = app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id])
        ->firstOrFail();
    $fixture['semester']->update(['registration_closes_at' => now()->subMinute()]);

    $this->actingAs($fixture['studentUser'])
        ->from(route('student.registrations.index'))
        ->delete(route('student.registrations.drop', $registration))
        ->assertSessionHasErrors('registration');

    expect($registration->fresh()->status)->toBe('registered');
});

it('prevents another student from dropping someone else registration', function () {
    $fixture = makeAcademicFixture();
    $registration = app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id])
        ->firstOrFail();
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');

    $this->actingAs($otherStudent)
        ->delete(route('student.registrations.drop', $registration))
        ->assertForbidden();
});

it('enforces the semester credit limit before writing registrations', function () {
    $fixture = makeAcademicFixture();
    $fixture['semester']->update(['max_credit_units' => 3]);
    $secondCourse = Course::create([
        'department_id' => $fixture['department']->id,
        'code' => 'CSC103',
        'title' => 'Programming Fundamentals',
        'credit_units' => 3,
        'level' => 100,
        'semester_type' => $fixture['semester']->type,
        'is_active' => true,
    ]);

    expect(fn () => app(CourseRegistrationService::class)->register(
        $fixture['student'],
        [$fixture['course']->id, $secondCourse->id],
    ))->toThrow(ValidationException::class);

    expect(CourseRegistration::query()->count())->toBe(0);
});

it('requires published passes for course prerequisites', function () {
    $fixture = makeAcademicFixture();
    $prerequisite = Course::create([
        'department_id' => $fixture['department']->id,
        'code' => 'CSC100',
        'title' => 'Computing Foundations',
        'credit_units' => 3,
        'level' => 100,
        'semester_type' => $fixture['semester']->type,
        'is_active' => true,
    ]);
    $fixture['course']->prerequisites()->attach($prerequisite);

    expect(fn () => app(CourseRegistrationService::class)->register(
        $fixture['student'],
        [$fixture['course']->id],
    ))->toThrow(ValidationException::class);

    expect(CourseRegistration::query()->count())->toBe(0);
});

it('blocks non-students from the course registration route', function () {
    $lecturer = User::factory()->create();
    $lecturer->assignRole('lecturer');

    $this->actingAs($lecturer)
        ->post(route('student.registrations.store'), ['courses' => [1]])
        ->assertForbidden();
});
