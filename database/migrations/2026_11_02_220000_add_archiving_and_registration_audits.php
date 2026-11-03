<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->timestamp('dropped_at')->nullable();
        });

        Schema::create('course_registration_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_registration_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('feedback')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['course_registration_id', 'created_at'], 'registration_audit_history_idx');
        });

        DB::statement('CREATE UNIQUE INDEX one_active_academic_session ON academic_sessions (is_active) WHERE is_active = TRUE');
        DB::statement('CREATE UNIQUE INDEX one_current_semester ON semesters (is_current) WHERE is_current = TRUE');
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registration_audit_logs');
        DB::statement('DROP INDEX IF EXISTS one_current_semester');
        DB::statement('DROP INDEX IF EXISTS one_active_academic_session');

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropColumn('dropped_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('course_assignments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
