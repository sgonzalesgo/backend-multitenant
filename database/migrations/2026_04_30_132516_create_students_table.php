<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('person_id');

            $table->string('student_code');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['tenant_id', 'person_id']);
            $table->unique(['tenant_id', 'student_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
