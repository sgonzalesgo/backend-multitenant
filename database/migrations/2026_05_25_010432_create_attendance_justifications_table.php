<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_justifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('attendance_record_id')
                ->constrained('attendance_records')
                ->cascadeOnDelete();

            $table->foreignUuid('attendance_session_id')
                ->constrained('attendance_sessions')
                ->cascadeOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignUuid('person_id')
                ->constrained('persons')
                ->cascadeOnDelete();

            $table->foreignUuid('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // medical, family, institutional, other
            $table->string('justification_type', 40)->default('other');

            $table->text('reason');

            $table->string('document_path')->nullable();

            // pending, approved, rejected
            $table->string('status', 40)->default('pending');

            $table->timestamp('reviewed_at')->nullable();

            $table->text('review_observation')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'attendance_record_id'],
                'attendance_justifications_unique_record'
            );

            $table->index(
                ['tenant_id', 'status'],
                'attendance_justifications_status_idx'
            );

            $table->index(
                ['tenant_id', 'student_id'],
                'attendance_justifications_student_idx'
            );

            $table->index(
                ['tenant_id', 'person_id'],
                'attendance_justifications_person_idx'
            );

            $table->index(
                ['tenant_id', 'attendance_session_id'],
                'attendance_justifications_session_idx'
            );

            $table->index(
                ['tenant_id', 'justification_type'],
                'attendance_justifications_type_idx'
            );

            $table->index(
                ['tenant_id', 'status', 'created_at'],
                'attendance_justifications_status_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_justifications');
    }
};
