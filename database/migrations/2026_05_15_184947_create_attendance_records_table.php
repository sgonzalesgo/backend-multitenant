<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('attendance_session_id')
                ->constrained('attendance_sessions')
                ->cascadeOnDelete();

            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();

            $table->string('status', 40)->default('present'); // present, absent, late, excused

            $table->timestamp('absence_notified_at')->nullable();

            $table->unsignedSmallInteger('late_minutes')->default(0);

            $table->text('observation')->nullable();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'attendance_session_id',
                'enrollment_id',
            ], 'attendance_records_unique_student');

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'person_id']);

            $table->index(
                ['tenant_id', 'status', 'absence_notified_at'],
                'attendance_records_absence_notification_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
