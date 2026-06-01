<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('grade_session_id')->constrained('grade_sessions')->cascadeOnDelete();

            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();

            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();

            $table->foreignUuid('person_id')->constrained('persons')->cascadeOnDelete();

            $table->decimal('final_score', 8, 2)->nullable();

            // passed, failed, incomplete, withdrawn
            $table->string('final_status', 40)->nullable();

            $table->string('qualitative_grade', 40)->nullable();

            $table->text('observation')->nullable();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'grade_session_id',
                'enrollment_id',
            ], 'grade_records_unique_student');

            $table->index(['tenant_id', 'student_id'], 'grade_records_student_idx');
            $table->index(['tenant_id', 'person_id'], 'grade_records_person_idx');
            $table->index(['tenant_id', 'final_status'], 'grade_records_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_records');
    }
};
