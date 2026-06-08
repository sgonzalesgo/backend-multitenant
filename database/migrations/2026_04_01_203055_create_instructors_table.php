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

            // Relaciones
            $table->uuid('tenant_id');
            $table->uuid('person_id');
            $table->uuid('department_id')->nullable();

            // Información institucional
            $table->string('code', 10)->unique();

            // Información académica
            $table->string('academic_title')->nullable(); // Dr., MSc., Ing.
            $table->string('academic_level')->nullable(); // Licenciatura, Maestría, Doctorado
            $table->string('specialty')->nullable();

            // Estado
            $table->string('status')->default('active');
            $table->timestamp('status_changed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
             * Foreign Keys
             */
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

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
             * Índices
             */
            $table->unique('person_id');

            $table->index('tenant_id');
            $table->index('department_id');
            $table->index('status');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
