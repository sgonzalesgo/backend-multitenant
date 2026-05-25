<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('calendar_event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();

            $table->foreignUuid('academic_schedule_id')
                ->nullable()
                ->constrained('academic_schedules')
                ->nullOnDelete();

            $table->foreignUuid('academic_schedule_frequency_id')
                ->nullable()
                ->constrained('academic_schedule_frequencies')
                ->nullOnDelete();

            $table->foreignUuid('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

            $table->foreignUuid('evaluation_period_id')
                ->nullable()
                ->constrained('evaluation_periods')
                ->nullOnDelete();

            $table->foreignUuid('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignUuid('specialty_id')
                ->nullable()
                ->constrained('specialties')
                ->nullOnDelete();

            $table->foreignUuid('parallel_id')
                ->constrained('parallels')
                ->cascadeOnDelete();

            $table->foreignUuid('modality_id')
                ->nullable()
                ->constrained('modalities')
                ->nullOnDelete();

            $table->foreignUuid('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete();

            $table->foreignUuid('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignUuid('instructor_id')
                ->constrained('instructors')
                ->cascadeOnDelete();

            $table->date('attendance_date');

            $table->string('status', 40)->default('draft'); // draft, closed

            $table->text('observation')->nullable();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'calendar_event_id', 'evaluation_period_id'],
                'attendance_sessions_unique_event_period'
            );

            $table->index(
                ['tenant_id', 'academic_year_id'],
                'attendance_sessions_year_idx'
            );

            $table->index(
                ['tenant_id', 'academic_year_id', 'evaluation_period_id'],
                'attendance_sessions_period_idx'
            );

            $table->index(
                ['tenant_id', 'academic_year_id', 'course_id', 'parallel_id'],
                'attendance_sessions_academic_context_idx'
            );

            $table->index(
                [
                    'tenant_id',
                    'academic_year_id',
                    'evaluation_period_id',
                    'course_id',
                    'specialty_id',
                    'parallel_id',
                    'modality_id',
                    'shift_id'
                ],
                'attendance_sessions_context_specialty_idx'
            );

            $table->index(
                [
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
                    'attendance_date'
                ],
                'attendance_sessions_full_context_idx'
            );

            $table->index(
                ['tenant_id', 'academic_year_id', 'evaluation_period_id', 'subject_id'],
                'attendance_sessions_subject_period_idx'
            );

            $table->index(
                ['tenant_id', 'academic_year_id', 'evaluation_period_id', 'instructor_id'],
                'attendance_sessions_instructor_period_idx'
            );

            $table->index(
                ['tenant_id', 'attendance_date'],
                'attendance_sessions_date_idx'
            );

            $table->index(
                ['tenant_id', 'status'],
                'attendance_sessions_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
