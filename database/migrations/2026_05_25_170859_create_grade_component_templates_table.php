<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_component_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            $table->foreignUuid('evaluation_period_id')->constrained('evaluation_periods')->cascadeOnDelete();

            $table->foreignUuid('educational_level_id')->nullable()->constrained('educational_levels')->nullOnDelete();

            $table->foreignUuid('course_id')->nullable()->constrained('courses')->nullOnDelete();

            $table->foreignUuid('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();

            $table->foreignUuid('modality_id')->nullable()->constrained('modalities')->nullOnDelete();

            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // basic_100, mixed_70_30, qualitative
            $table->string('grading_mode', 50);

            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
                'code',
            ], 'grade_component_templates_unique_code');

            $table->index([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
            ], 'grade_component_templates_period_idx');

            $table->index([
                'tenant_id',
                'educational_level_id',
                'course_id',
            ], 'grade_component_templates_level_course_idx');

            $table->index([
                'tenant_id',
                'grading_mode',
            ], 'grade_component_templates_grading_mode_idx');

            $table->index([
                'tenant_id',
                'is_active',
            ], 'grade_component_templates_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_component_templates');
    }
};
