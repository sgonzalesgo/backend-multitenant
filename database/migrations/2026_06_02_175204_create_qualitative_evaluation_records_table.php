<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('qualitative_evaluation_session_id');

            $table->uuid('student_id');
            $table->uuid('enrollment_id');

            $table->timestamps();

            $table->foreign('qualitative_evaluation_session_id')
                ->references('id')
                ->on('qualitative_evaluation_sessions')
                ->cascadeOnDelete();

            $table->foreign('student_id')
                ->references('id')
                ->on('students');

            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments');

            $table->index('tenant_id');

            $table->unique([
                'qualitative_evaluation_session_id',
                'student_id'
            ], 'qual_eval_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_records');
    }
};
