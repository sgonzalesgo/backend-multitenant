<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_legal_representatives', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignUuid('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignUuid('legal_representative_id')
                ->constrained('legal_representatives')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('relationship_type', 80);
            $table->text('description')->nullable();

            $table->boolean('is_billable')->default(false);
            $table->boolean('is_emergency_contact')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'student_id',
                'legal_representative_id',
                'relationship_type',
            ], 'student_representative_relationship_unique');

            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'legal_representative_id']);
            $table->index(['is_billable']);
            $table->index(['is_emergency_contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_legal_representatives');
    }
};
