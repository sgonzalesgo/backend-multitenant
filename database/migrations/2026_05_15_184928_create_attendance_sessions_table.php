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

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();

            $table->foreignUuid('academic_schedule_id')
                ->nullable()
                ->constrained('academic_schedules')
                ->nullOnDelete();

            $table->foreignUuid('academic_schedule_frequency_id')
                ->nullable()
                ->constrained('academic_schedule_frequencies')
                ->nullOnDelete();

            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('parallel_id')->constrained('parallels')->cascadeOnDelete();

            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('instructor_id')->constrained('instructors')->cascadeOnDelete();

            $table->date('attendance_date');

            $table->string('status', 40)->default('draft'); // draft, closed
            $table->text('observation')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'calendar_event_id'], 'attendance_sessions_unique_event');

            $table->index(['tenant_id', 'academic_year_id']);
            $table->index(['tenant_id', 'academic_year_id', 'course_id', 'parallel_id'], 'attendance_sessions_academic_context_idx');
            $table->index(['tenant_id', 'academic_year_id', 'subject_id']);
            $table->index(['tenant_id', 'academic_year_id', 'instructor_id']);
            $table->index(['tenant_id', 'attendance_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
