<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('academic_year_id');
            $table->uuid('evaluation_period_id');

            $table->uuid('course_id');
            $table->uuid('specialty_id')->nullable();
            $table->uuid('parallel_id');

            $table->uuid('modality_id')->nullable();
            $table->uuid('shift_id')->nullable();

            $table->uuid('subject_id');

            $table->string('name');
            $table->boolean('is_closed')->default(false);

            $table->timestamps();

            $table->foreign('academic_year_id')->references('id')->on('academic_years');
            $table->foreign('evaluation_period_id')->references('id')->on('evaluation_periods');

            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('specialty_id')->references('id')->on('specialties');

            $table->foreign('parallel_id')->references('id')->on('parallels');

            $table->foreign('modality_id')->references('id')->on('modalities');
            $table->foreign('shift_id')->references('id')->on('shifts');

            $table->foreign('subject_id')->references('id')->on('subjects');

            $table->index('tenant_id');

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
                'course_id',
                'specialty_id',
                'parallel_id',
                'modality_id',
                'shift_id',
                'subject_id'
            ], 'qual_eval_session_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_sessions');
    }
};
