<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();
            $table->unique(['faculty_id', 'name']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->unsignedSmallInteger('max_credit_units')->default(24);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['academic_session_id', 'type']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('title');
            $table->unsignedSmallInteger('credit_units');
            $table->unsignedSmallInteger('level');
            $table->string('semester_type', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['department_id', 'level']);
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_course_id')->constrained('courses')->restrictOnDelete();
            $table->primary(['course_id', 'prerequisite_course_id']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('student_number', 40)->unique();
            $table->unsignedSmallInteger('level');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('staff_number', 40)->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('email')->constrained()->restrictOnDelete();
        });

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('lecturer_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'lecturer_id', 'semester_id']);
            $table->index(['lecturer_id', 'semester_id']);
        });

        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('registered');
            $table->timestamps();
            $table->unique(['student_id', 'course_id', 'semester_id']);
            $table->index(['student_id', 'semester_id']);
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_registration_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('ca_score');
            $table->unsignedTinyInteger('exam_score');
            $table->unsignedTinyInteger('total_score');
            $table->char('grade', 1);
            $table->decimal('grade_point', 3, 2);
            $table->string('status', 20)->default('draft');
            $table->text('latest_feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        Schema::create('result_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('feedback')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['result_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_audit_logs');
        Schema::dropIfExists('results');
        Schema::dropIfExists('course_registrations');
        Schema::dropIfExists('course_assignments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_sessions');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
    }
};
