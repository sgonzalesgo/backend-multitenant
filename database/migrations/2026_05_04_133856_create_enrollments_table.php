<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->string('enrollment_code', 80)->unique();

            $table->uuid('student_id');
            $table->uuid('academic_year_id');
            $table->uuid('course_id')->nullable();
            $table->uuid('parallel_id')->nullable();
            $table->uuid('shift_id')->nullable();
            $table->uuid('enrollment_status_id')->nullable();
            $table->uuid('assigned_user_id')->nullable();

            $table->boolean('is_new')->default(false);
            $table->boolean('is_conditional')->default(false);
            $table->boolean('is_active')->default(true);

            $table->text('observation')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('student_email_sent_at')->nullable();
            $table->timestamp('representatives_email_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('parallel_id')->references('id')->on('parallels');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('enrollment_status_id')->references('id')->on('enrollment_statuses');
            $table->foreign('assigned_user_id')->references('id')->on('users');

            $table->unique([
                'tenant_id',
                'student_id',
                'academic_year_id',
                'course_id',
                'parallel_id',
                'shift_id',
            ], 'enrollments_unique_student_period');

            $table->index(['tenant_id', 'enrollment_code']);
            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'academic_year_id']);
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
