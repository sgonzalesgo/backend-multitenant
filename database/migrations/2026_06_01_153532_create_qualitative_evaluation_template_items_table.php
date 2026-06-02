<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_template_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');

            $table->uuid('qualitative_evaluation_template_id');
            $table->uuid('qualitative_skill_definition_id');

            $table->integer('default_order')->default(1);
            $table->boolean('is_required')->default(true);

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants');

            $table->foreign('qualitative_evaluation_template_id')
                ->references('id')
                ->on('qualitative_evaluation_templates')
                ->cascadeOnDelete();

            $table->foreign('qualitative_skill_definition_id')
                ->references('id')
                ->on('qualitative_skill_definitions');

            $table->unique([
                'tenant_id',
                'qualitative_evaluation_template_id',
                'qualitative_skill_definition_id',
            ], 'qual_eval_template_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_template_items');
    }
};
