<?php

use App\Enums\ResultStatus;
use App\Enums\SemesterType;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseRegistration;
use App\Models\Lecturer;
use App\Models\Result;
use App\Models\ResultAuditLog;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicReportService;
use App\Services\CourseRegistrationService;
use App\Services\ResultManagementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeResultWorkflowFixture(): array
{
    $fixture = makeAcademicFixture();
    $lecturerUser = User::factory()->create();
    $lecturerUser->assignRole(UserRole::Lecturer->value);
    $lecturer = Lecturer::create([
        'user_id' => $lecturerUser->id,
        'department_id' => $fixture['department']->id,
        'staff_number' => 'STAFF-001',
    ]);
    CourseAssignment::create([
        'course_id' => $fixture['course']->id,
        'lecturer_id' => $lecturer->id,
        'semester_id' => $fixture['semester']->id,
    ]);
    $departmentAdmin = User::factory()->create(['department_id' => $fixture['department']->id]);
    $departmentAdmin->assignRole(UserRole::DepartmentAdmin->value);

    $registration = app(CourseRegistrationService::class)
        ->register($fixture['student'], [$fixture['course']->id])
        ->first();

    return [...$fixture, 'lecturerUser' => $lecturerUser, 'departmentAdmin' => $departmentAdmin, 'registration' => $registration];
}

it('locks submitted results and only publishes after departmental approval', function () {
    $fixture = makeResultWorkflowFixture();
    $service = app(ResultManagementService::class);
    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 25, 65);

    expect($result->status)->toBe(ResultStatus::Draft)
        ->and($result->total_score)->toBe(90)
        ->and($result->grade)->toBe('A');

    $result = $service->submit($fixture['lecturerUser'], $result);
    expect($result->status)->toBe(ResultStatus::Submitted);

    expect(app(AcademicReportService::class)->resultStatement($fixture['student'])['courses'])->toBeEmpty();

    expect(fn () => $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 10, 10))
        ->toThrow(ValidationException::class);

    $result = $service->approve($fixture['departmentAdmin'], $result);
    expect($result->status)->toBe(ResultStatus::Approved);

    $result = $service->publish($fixture['departmentAdmin'], $result);
    expect($result->status)->toBe(ResultStatus::Published)
        ->and(ResultAuditLog::query()->count())->toBe(4)
        ->and(app(AcademicReportService::class)->resultStatement($fixture['student'])['cgpa'])->toBe(5.0);
});

it('requires feedback for rejection and permits a corrected resubmission', function () {
    $fixture = makeResultWorkflowFixture();
    $service = app(ResultManagementService::class);
    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 10, 20);
    $result = $service->submit($fixture['lecturerUser'], $result);

    expect(fn () => $service->reject($fixture['departmentAdmin'], $result, ''))
        ->toThrow(ValidationException::class);

    $result = $service->reject($fixture['departmentAdmin'], $result, 'Please verify the exam score.');
    expect($result->status)->toBe(ResultStatus::Rejected);

    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 20, 35);
    expect($result->status)->toBe(ResultStatus::Draft)
        ->and($result->latest_feedback)->toBeNull();
});

it('does not let super administrators approve departmental results', function () {
    $fixture = makeResultWorkflowFixture();
    $service = app(ResultManagementService::class);
    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 20, 40);
    $result = $service->submit($fixture['lecturerUser'], $result);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(UserRole::SuperAdmin->value);

    expect(fn () => $service->approve($superAdmin, $result))
        ->toThrow(HttpException::class);
});

it('reopens published results for reasoned correction and requires the normal approval lifecycle again', function () {
    $fixture = makeResultWorkflowFixture();
    $service = app(ResultManagementService::class);
    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 25, 65);
    $result = $service->submit($fixture['lecturerUser'], $result);
    $result = $service->approve($fixture['departmentAdmin'], $result);
    $result = $service->publish($fixture['departmentAdmin'], $result);

    $result = $service->requestCorrection($fixture['departmentAdmin'], $result, 'The examination mark was entered incorrectly.');
    expect($result->status)->toBe(ResultStatus::Draft)
        ->and($result->published_at)->toBeNull()
        ->and($result->latest_feedback)->toBe('The examination mark was entered incorrectly.')
        ->and($result->auditLogs()->latest('id')->first()->metadata)->toMatchArray([
            'ca_score' => 25,
            'exam_score' => 65,
            'total_score' => 90,
            'grade' => 'A',
        ]);

    $result = $service->saveDraft($fixture['lecturerUser'], $fixture['registration'], 20, 50);
    $result = $service->submit($fixture['lecturerUser'], $result);
    $result = $service->approve($fixture['departmentAdmin'], $result);
    $result = $service->publish($fixture['departmentAdmin'], $result);

    expect($result->status)->toBe(ResultStatus::Published)
        ->and($result->total_score)->toBe(70)
        ->and($result->auditLogs()->where('action', 'correction_requested')->exists())->toBeTrue();
});

it('rounds CGPA half up and counts only the latest published attempt for each course', function () {
    $fixture = makeAcademicFixture();
    $fixture['course']->update(['credit_units' => 1]);
    $otherCourse = Course::create([
        'department_id' => $fixture['department']->id,
        'code' => 'CSC102',
        'title' => 'Discrete Mathematics',
        'credit_units' => 7,
        'level' => 100,
        'semester_type' => SemesterType::First,
        'is_active' => true,
    ]);

    $firstRegistration = CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $fixture['course']->id,
        'semester_id' => $fixture['semester']->id,
    ]);
    $otherRegistration = CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $otherCourse->id,
        'semester_id' => $fixture['semester']->id,
    ]);
    foreach ([[$firstRegistration, 40, 'E', 1], [$otherRegistration, 30, 'F', 0]] as [$registration, $score, $grade, $point]) {
        Result::create([
            'course_registration_id' => $registration->id,
            'ca_score' => 0,
            'exam_score' => $score,
            'total_score' => $score,
            'grade' => $grade,
            'grade_point' => $point,
            'status' => ResultStatus::Published,
            'published_at' => now(),
        ]);
    }

    $reports = app(AcademicReportService::class);
    expect($reports->resultStatement($fixture['student'])['cgpa'])->toBe(0.13);

    $laterSession = AcademicSession::create([
        'name' => '2027/2028',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-12-31',
        'is_active' => false,
    ]);
    $laterSemester = Semester::create([
        'academic_session_id' => $laterSession->id,
        'type' => SemesterType::First,
        'starts_on' => '2027-02-01',
        'ends_on' => '2027-06-30',
        'max_credit_units' => 24,
        'is_current' => false,
        'is_active' => true,
    ]);
    $retake = CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $fixture['course']->id,
        'semester_id' => $laterSemester->id,
    ]);
    Result::create([
        'course_registration_id' => $retake->id,
        'ca_score' => 0,
        'exam_score' => 30,
        'total_score' => 30,
        'grade' => 'F',
        'grade_point' => 0,
        'status' => ResultStatus::Published,
        'published_at' => now(),
    ]);

    expect($reports->resultStatement($fixture['student'])['cgpa'])->toBe(0.0);

    $sameDateSemester = Semester::create([
        'academic_session_id' => $laterSession->id,
        'type' => SemesterType::Second,
        'starts_on' => $laterSemester->starts_on,
        'ends_on' => '2027-06-30',
        'max_credit_units' => 24,
        'is_current' => false,
        'is_active' => true,
    ]);
    $sameDateRetake = CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $fixture['course']->id,
        'semester_id' => $sameDateSemester->id,
    ]);
    Result::create([
        'course_registration_id' => $sameDateRetake->id,
        'ca_score' => 0,
        'exam_score' => 40,
        'total_score' => 40,
        'grade' => 'E',
        'grade_point' => 1,
        'status' => ResultStatus::Published,
        'published_at' => now(),
    ]);

    expect($reports->resultStatement($fixture['student'])['cgpa'])->toBe(0.13);
});
