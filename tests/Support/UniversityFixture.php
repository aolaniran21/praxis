<?php

use App\Enums\SemesterType;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;

function makeAcademicFixture(): array
{
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
    $session = AcademicSession::create([
        'name' => '2026/2027',
        'starts_on' => now()->startOfYear()->toDateString(),
        'ends_on' => now()->addYear()->endOfYear()->toDateString(),
        'is_active' => true,
    ]);
    $semester = Semester::create([
        'academic_session_id' => $session->id,
        'type' => SemesterType::First,
        'starts_on' => now()->startOfMonth()->toDateString(),
        'ends_on' => now()->addMonths(4)->endOfMonth()->toDateString(),
        'registration_opens_at' => now()->subDay(),
        'registration_closes_at' => now()->addDay(),
        'max_credit_units' => 24,
        'is_current' => true,
    ]);
    $studentUser = User::factory()->create();
    $studentUser->assignRole(UserRole::Student->value);
    $student = Student::create([
        'user_id' => $studentUser->id,
        'program_id' => $program->id,
        'student_number' => 'STU-001',
        'level' => 100,
        'is_active' => true,
    ]);
    $course = Course::create([
        'department_id' => $department->id,
        'code' => 'CSC101',
        'title' => 'Introduction to Computing',
        'credit_units' => 3,
        'level' => 100,
        'semester_type' => SemesterType::First,
        'is_active' => true,
    ]);

    return compact('faculty', 'department', 'program', 'session', 'semester', 'studentUser', 'student', 'course');
}
