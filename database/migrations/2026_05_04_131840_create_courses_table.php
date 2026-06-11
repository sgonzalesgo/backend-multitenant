<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignUuid('educational_level_id')
                ->constrained('educational_levels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignUuid('instructor_id')
                ->nullable()
                ->constrained('instructors')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedInteger('level_number');
            $table->string('code', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->unsignedInteger('capacity')->default(0);

            $table->unsignedSmallInteger('credits')->nullable();
            $table->unsignedSmallInteger('theoretical_hours')->nullable();
            $table->unsignedSmallInteger('practical_hours')->nullable();
            $table->unsignedSmallInteger('total_hours')->nullable();

            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'educational_level_id']);
            $table->index(['tenant_id', 'instructor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
