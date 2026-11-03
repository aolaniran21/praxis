<?php

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('scopes department reports and exports published results', function () {
    $fixture = makeAcademicFixture();
    $fixture['studentUser']->update(['name' => '=2+2']);
    $firstRegistration = CourseRegistration::create([
        'student_id' => $fixture['student']->id,
        'course_id' => $fixture['course']->id,
        'semester_id' => $fixture['semester']->id,
    ]);

    $secondFaculty = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART']);
    $secondDepartment = Department::create([
        'faculty_id' => $secondFaculty->id,
        'name' => 'History',
        'code' => 'HIS',
    ]);
    $secondProgram = Program::create([
        'department_id' => $secondDepartment->id,
        'name' => 'History',
        'code' => 'BA-HIS',
    ]);
    $secondStudentUser = User::factory()->create(['name' => 'Second Department Student']);
    $secondStudent = Student::create([
        'user_id' => $secondStudentUser->id,
        'program_id' => $secondProgram->id,
        'student_number' => 'STU-002',
        'level' => 100,
        'is_active' => true,
    ]);
    $secondCourse = Course::create([
        'department_id' => $secondDepartment->id,
        'code' => 'HIS101',
        'title' => 'Introduction to History',
        'credit_units' => 3,
        'level' => 100,
        'semester_type' => $fixture['course']->semester_type,
        'is_active' => true,
    ]);
    $secondRegistration = CourseRegistration::create([
        'student_id' => $secondStudent->id,
        'course_id' => $secondCourse->id,
        'semester_id' => $fixture['semester']->id,
    ]);

    foreach ([$firstRegistration, $secondRegistration] as $registration) {
        Result::create([
            'course_registration_id' => $registration->id,
            'ca_score' => 25,
            'exam_score' => 60,
            'total_score' => 85,
            'grade' => 'A',
            'grade_point' => 5,
            'status' => ResultStatus::Published,
            'published_at' => now(),
        ]);
    }

    $departmentAdmin = User::factory()->create(['department_id' => $fixture['department']->id]);
    $departmentAdmin->assignRole(UserRole::DepartmentAdmin->value);
    $this->actingAs($departmentAdmin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('STU-001')
        ->assertDontSee('STU-002');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($superAdmin)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('STU-001')
        ->assertSee('STU-002');

    $this->get(route('reports.index', [
        'semester_id' => $fixture['semester']->id,
        'program_id' => $fixture['program']->id,
        'level' => 100,
        'status' => 'published',
    ]))
        ->assertOk()
        ->assertSee('Semester GPA')
        ->assertSee('STU-001')
        ->assertDontSee('STU-002');

    $export = $this->get(route('reports.export'));
    $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($export->streamedContent())
        ->toContain('Student number')
        ->toContain("'=2+2");
});
