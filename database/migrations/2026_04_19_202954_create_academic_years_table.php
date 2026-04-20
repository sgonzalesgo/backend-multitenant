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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tenant / colegio propietario del año académico
            $table->uuid('tenant_id');

            // Datos principales
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('description', 255)->nullable();

            // Fechas del año académico
            $table->date('start_date');
            $table->date('end_date');

            // Estados
            $table->boolean('is_active')->default(true);
            $table->boolean('is_current')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tenant_id');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('is_active');
            $table->index('is_current');

            // Unicidad por tenant
            $table->unique(['tenant_id', 'code'], 'academic_years_tenant_code_unique');
            $table->unique(['tenant_id', 'name'], 'academic_years_tenant_name_unique');

            // Si tienes tabla tenants, puedes dejar esta FK
            // Si todavía no quieres atarla, la quitamos
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
