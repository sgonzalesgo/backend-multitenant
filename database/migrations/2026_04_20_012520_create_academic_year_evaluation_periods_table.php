<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_year_evaluation_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('academic_year_id');
            $table->uuid('evaluation_period_id');

            $table->unsignedInteger('order')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('evaluation_period_id')
                ->references('id')
                ->on('evaluation_periods')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['academic_year_id', 'evaluation_period_id'],
                'ayep_academic_year_evaluation_period_unique'
            );

            $table->unique(
                ['academic_year_id', 'order'],
                'ayep_academic_year_order_unique'
            );

            $table->index('academic_year_id');
            $table->index('evaluation_period_id');
            $table->index('order');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_year_evaluation_periods');
    }
};
