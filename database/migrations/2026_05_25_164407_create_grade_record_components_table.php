<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_record_components', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->foreignUuid('grade_record_id')->constrained('grade_records')->cascadeOnDelete();

            $table->foreignUuid('grade_component_id')->constrained('grade_components')->cascadeOnDelete();

            $table->decimal('score', 8, 2)->nullable();

            $table->string('qualitative_grade', 40)->nullable();

            $table->text('observation')->nullable();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'grade_record_id',
                'grade_component_id',
            ], 'grade_record_components_unique_component');

            $table->index([
                'tenant_id',
                'grade_component_id',
            ], 'grade_record_components_component_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_record_components');
    }
};
