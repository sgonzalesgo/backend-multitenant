<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();

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
                ->constrained('modalities')
                ->cascadeOnDelete();

            $table->foreignUuid('shift_id')
                ->constrained('shifts')
                ->cascadeOnDelete();

            $table->string('status', 80)->default('draft');

            $table->text('general_observation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'course_id',
                'specialty_id',
                'parallel_id',
                'modality_id',
                'shift_id',
            ], 'academic_schedules_unique_context');

            $table->index(['tenant_id', 'academic_year_id']);
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'specialty_id']);
            $table->index(['tenant_id', 'parallel_id']);
            $table->index(['tenant_id', 'shift_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_schedules');
    }
};
