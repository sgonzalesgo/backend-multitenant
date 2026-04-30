<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('person_id');
            $table->uuid('department_id')->nullable();

            $table->string('academic_title')->nullable();
            $table->string('academic_level')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unique('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
