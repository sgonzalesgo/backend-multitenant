<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_non_working_days', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('academic_year_id')
                ->nullable()
                ->constrained('academic_years')
                ->nullOnDelete();

            $table->date('date');

            $table->string('name', 180);

            $table->string('type', 80)->default('holiday');
            // holiday, suspension, emergency, institutional, other

            $table->boolean('affects_attendance')->default(true);
            $table->boolean('affects_calendar')->default(true);
            $table->boolean('is_active')->default(true);

            $table->text('observation')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'academic_year_id',
                'date',
            ], 'academic_non_working_days_unique_date');

            $table->index(['tenant_id', 'academic_year_id']);
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'affects_attendance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_non_working_days');
    }
};
