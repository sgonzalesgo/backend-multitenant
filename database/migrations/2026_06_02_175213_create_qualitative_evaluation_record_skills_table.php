<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualitative_evaluation_record_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');

            $table->uuid('qualitative_evaluation_record_id');

            $table->uuid('qualitative_evaluation_component_id');

            $table->enum('value', [
                'I',
                'EP',
                'A',
                'NE'
            ])->nullable();

            $table->text('observation')->nullable();

            $table->timestamps();

            $table->foreign('qualitative_evaluation_record_id')
                ->references('id')
                ->on('qualitative_evaluation_records')
                ->cascadeOnDelete();

            $table->foreign('qualitative_evaluation_component_id')
                ->references('id')
                ->on('qualitative_evaluation_components')
                ->cascadeOnDelete();

            $table->index('tenant_id');

            $table->unique([
                'qualitative_evaluation_record_id',
                'qualitative_evaluation_component_id'
            ], 'qual_eval_record_skill_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitative_evaluation_record_skills');
    }
};
