<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->uuid('academic_year_id');
            $table->uuid('evaluation_period_id');
            $table->uuid('course_id');
            $table->uuid('parallel_id');

            $table->uuid('modality_id')->nullable();
            $table->uuid('shift_id')->nullable();
            $table->uuid('subject_id')->nullable();

            $table->uuid('qualitative_evaluation_template_id')->nullable();
            $table->uuid('qualitative_skill_definition_id');

            $table->integer('order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years');

            $table->foreign('evaluation_period_id')
                ->references('id')
                ->on('evaluation_periods');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses');

            $table->foreign('parallel_id')
                ->references('id')
                ->on('parallels');

            $table->foreign('modality_id')
                ->references('id')
                ->on('modalities');

            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts');

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects');

            $table->foreign('qualitative_evaluation_template_id')
                ->references('id')
                ->on('qualitative_evaluation_templates');

            $table->foreign('qualitative_skill_definition_id')
                ->references('id')
                ->on('qualitative_skill_definitions');

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
                'course_id',
                'parallel_id',
                'modality_id',
                'shift_id',
                'subject_id',
                'qualitative_skill_definition_id',
            ], 'qual_eval_component_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_components');
    }
};
