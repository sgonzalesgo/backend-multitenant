<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('evaluation_period_id')->constrained('evaluation_periods')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->foreignUuid('parallel_id')->constrained('parallels')->cascadeOnDelete();
            $table->foreignUuid('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('instructor_id')->constrained('instructors')->cascadeOnDelete();

            $table->string('status', 40)->default('draft');
            $table->text('observation')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
                'course_id',
                'specialty_id',
                'parallel_id',
                'modality_id',
                'shift_id',
                'subject_id',
                'instructor_id',
            ], 'grade_sessions_unique_context');

            $table->index([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
            ], 'grade_sessions_period_idx');

            $table->index([
                'tenant_id',
                'course_id',
                'parallel_id',
                'subject_id',
            ], 'grade_sessions_course_subject_idx');

            $table->index([
                'tenant_id',
                'status',
            ], 'grade_sessions_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_sessions');
    }
};
