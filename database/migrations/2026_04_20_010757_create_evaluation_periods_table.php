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
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('academic_year_id');

            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('description', 255)->nullable();

            $table->unsignedInteger('default_order')->default(1);
            $table->date('start_date');
            $table->date('end_date');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('academic_year_id')
                ->references('id')
                ->on('academic_years')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(
                ['academic_year_id', 'code'],
                'evaluation_periods_academic_year_code_unique'
            );

            $table->unique(
                ['academic_year_id', 'name'],
                'evaluation_periods_academic_year_name_unique'
            );

            $table->unique(
                ['academic_year_id', 'default_order'],
                'evaluation_periods_academic_year_order_unique'
            );

            $table->index('academic_year_id');
            $table->index('default_order');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
