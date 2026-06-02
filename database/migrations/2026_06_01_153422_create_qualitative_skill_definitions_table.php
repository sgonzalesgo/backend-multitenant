<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_skill_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->uuid('qualitative_evaluation_area_id');

            $table->string('code', 50);
            $table->text('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('qualitative_evaluation_area_id')
                ->references('id')
                ->on('qualitative_evaluation_areas');

            $table->unique([
                'tenant_id',
                'qualitative_evaluation_area_id',
                'code',
            ], 'qual_skill_definitions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_skill_definitions');
    }
};
