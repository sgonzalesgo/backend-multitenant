<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('code', 100)->unique();

            // Relación con person (responsable)
            $table->uuid('person_id')->nullable();

            // Multi-tenant
            $table->uuid('tenant_id');

            // Estado (siguiendo tu patrón en User)
            $table->string('status', 50)->default('active');

            $table->timestamps();

            // FK person
            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            // FK tenant
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
