<?php

namespace Database\Seeders;

use App\Enums\SemesterType;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UniversityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoPassword = config('app.demo_password');

        if (app()->environment('production') && blank($demoPassword)) {
            throw new RuntimeException('DEMO_PASSWORD must be configured before seeding demo accounts in production.');
        }

        $demoPassword = $demoPassword ?: 'ChangeMe123!';

        $faculty = Faculty::firstOrCreate(['code' => 'SCI'], ['name' => 'Faculty of Science']);
        $department = Department::firstOrCreate(
            ['code' => 'CSC'],
            ['faculty_id' => $faculty->id, 'name' => 'Computer Science'],
        );
        $program = Program::firstOrCreate(
            ['code' => 'BSC-CS'],
            ['department_id' => $department->id, 'name' => 'B.Sc. Computer Science'],
        );

        $session = AcademicSession::firstOrCreate(
            ['name' => now()->year.'/'.(now()->year + 1)],
            [
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on' => now()->addYear()->endOfYear()->toDateString(),
                'is_active' => true,
            ],
        );

        $semester = Semester::firstOrCreate(
            ['academic_session_id' => $session->id, 'type' => SemesterType::First->value],
            [
                'starts_on' => now()->startOfMonth()->subMonth()->toDateString(),
                'ends_on' => now()->addMonths(3)->endOfMonth()->toDateString(),
                'registration_opens_at' => now()->subMonth(),
                'registration_closes_at' => now()->addMonth(),
                'max_credit_units' => 24,
                'is_current' => true,
            ],
        );

        $secondSemester = Semester::firstOrCreate(
            ['academic_session_id' => $session->id, 'type' => SemesterType::Second->value],
            [
                'starts_on' => now()->addMonths(5)->startOfMonth()->toDateString(),
                'ends_on' => now()->addMonths(9)->endOfMonth()->toDateString(),
                'max_credit_units' => 24,
                'is_current' => false,
            ],
        );

        $superAdmin = $this->user('University Administrator', 'admin@university.test', $demoPassword);
        $superAdmin->syncRoles(UserRole::SuperAdmin->value);

        $departmentAdmin = $this->user('Department Administrator', 'department.admin@university.test', $demoPassword);
        $departmentAdmin->forceFill(['department_id' => $department->id])->save();
        $departmentAdmin->syncRoles(UserRole::DepartmentAdmin->value);

        $lecturerUser = $this->user('Demo Lecturer', 'lecturer@university.test', $demoPassword);
        $lecturerUser->syncRoles(UserRole::Lecturer->value);
        $lecturer = Lecturer::firstOrCreate(
            ['user_id' => $lecturerUser->id],
            ['department_id' => $department->id, 'staff_number' => 'STAFF-0001'],
        );

        $studentUser = $this->user('Demo Student', 'student@university.test', $demoPassword);
        $studentUser->syncRoles(UserRole::Student->value);
        Student::firstOrCreate(
            ['user_id' => $studentUser->id],
            ['program_id' => $program->id, 'student_number' => 'STU-2026-0001', 'level' => 100],
        );

        $courses = [
            ['code' => 'CSC101', 'title' => 'Introduction to Computing', 'credit_units' => 3],
            ['code' => 'CSC103', 'title' => 'Programming Fundamentals', 'credit_units' => 3],
            ['code' => 'MTH101', 'title' => 'Calculus I', 'credit_units' => 3],
        ];

        foreach ($courses as $attributes) {
            $course = Course::firstOrCreate(
                ['code' => $attributes['code']],
                [
                    ...$attributes,
                    'department_id' => $department->id,
                    'level' => 100,
                    'semester_type' => SemesterType::First->value,
                    'is_active' => true,
                ],
            );

            CourseAssignment::firstOrCreate([
                'course_id' => $course->id,
                'lecturer_id' => $lecturer->id,
                'semester_id' => $semester->id,
            ]);
        }

        $this->command?->info(sprintf(
            'Demo accounts are seeded with the configured shared password. Current semester: %s (%s).',
            $session->name,
            $semester->type->value,
        ));

        unset($secondSemester, $superAdmin);
    }

    private function user(string $name, string $email, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );
    }
}
