<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_component_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // FORMATIVE_100, FORMATIVE_70, SUMMATIVE_30, BEHAVIOR
            $table->string('component_key', 100);

            // numeric, behavior, qualitative
            $table->string('component_type', 50)->default('numeric');

            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'component_key',
            ], 'grade_component_definitions_unique_key');

            $table->unique([
                'tenant_id',
                'code',
            ], 'grade_component_definitions_unique_code');

            $table->index([
                'tenant_id',
                'component_type',
            ], 'grade_component_definitions_component_type_idx');

            $table->index([
                'tenant_id',
                'is_active',
            ], 'grade_component_definitions_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_component_definitions');
    }
};
