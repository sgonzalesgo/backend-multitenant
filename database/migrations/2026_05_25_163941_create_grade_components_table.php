<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_components', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();

            $table->foreignUuid('evaluation_period_id')->constrained('evaluation_periods')->cascadeOnDelete();

            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();

            $table->foreignUuid('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();

            $table->foreignUuid('parallel_id')->nullable()->constrained('parallels')->nullOnDelete();

            $table->foreignUuid('modality_id')->nullable()->constrained('modalities')->nullOnDelete();

            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->foreignUuid('evaluation_type_id')->nullable()->constrained('evaluation_types')->nullOnDelete();

            // FORMATIVE_100, FORMATIVE_70, SUMMATIVE_30, BEHAVIOR
            $table->string('component_key', 100);

            // numeric, behavior, qualitative
            $table->string('component_type', 50)->default('numeric');

            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('weight', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(10);

            $table->unsignedSmallInteger('default_order')->default(1);

            $table->boolean('is_required')->default(true);
            $table->boolean('is_system_calculated')->default(false);
            $table->boolean('is_active')->default(true);

            $table->json('settings')->nullable();

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
                'component_key',
            ], 'grade_components_unique_context_key');

            $table->index([
                'tenant_id',
                'academic_year_id',
                'evaluation_period_id',
            ], 'grade_components_period_idx');

            $table->index([
                'tenant_id',
                'course_id',
                'parallel_id',
                'subject_id',
            ], 'grade_components_course_parallel_subject_idx');

            $table->index([
                'tenant_id',
                'evaluation_type_id',
            ], 'grade_components_type_idx');

            $table->index([
                'tenant_id',
                'component_key',
            ], 'grade_components_component_key_idx');

            $table->index([
                'tenant_id',
                'component_type',
            ], 'grade_components_component_type_idx');

            $table->index([
                'tenant_id',
                'is_active',
            ], 'grade_components_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_components');
    }
};
