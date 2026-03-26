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
        Schema::create('impersonation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Id opaco que viajará al frontend para revertir de forma segura
            $table->string('session_id', 100)->unique();

            // Usuario original (admin) que inicia la suplantación
            $table->uuid('impersonator_id');

            // Usuario que está siendo suplantado
            $table->uuid('impersonated_id');

            // Tenant original del actor al iniciar la suplantación
            $table->uuid('actor_tenant_id')->nullable();

            // Tokens backup del actor original (guardados server-side)
            $table->text('backup_access_token');
            $table->string('backup_refresh_token', 128);

            // Metadatos de ciclo de vida
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index('impersonator_id');
            $table->index('impersonated_id');
            $table->index('actor_tenant_id');
            $table->index('expires_at');
            $table->index('ended_at');
            $table->index('revoked_at');

            $table->foreign('impersonator_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('impersonated_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('actor_tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_sessions');
    }
};
