<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_component_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('grade_component_template_id')
                ->constrained('grade_component_templates')
                ->cascadeOnDelete();

            $table->foreignUuid('grade_component_definition_id')
                ->constrained('grade_component_definitions')
                ->restrictOnDelete();

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
                'grade_component_template_id',
                'grade_component_definition_id',
            ], 'grade_component_template_items_unique_definition');

            $table->index([
                'tenant_id',
                'grade_component_template_id',
            ], 'grade_component_template_items_template_idx');

            $table->index([
                'tenant_id',
                'grade_component_definition_id',
            ], 'grade_component_template_items_definition_idx');

            $table->index([
                'tenant_id',
                'is_active',
            ], 'grade_component_template_items_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_component_template_items');
    }
};
