<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->string('name');
            $table->text('description')->nullable();

            $table->uuid('educational_level_id')->nullable();
            $table->uuid('course_id')->nullable();
            $table->uuid('evaluation_period_id')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('educational_level_id')
                ->references('id')
                ->on('educational_levels');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses');

            $table->foreign('evaluation_period_id')
                ->references('id')
                ->on('evaluation_periods');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_templates');
    }
};
