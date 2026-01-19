<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Actor (normalmente User)
            $table->string('actor_type', 191)->nullable();
            $table->uuid('actor_id')->nullable()->index();

            // Multitenancy (opcional)
            $table->uuid('tenant_id')->nullable()->index();

            // Evento y sujeto (sujeto ahora opcional)
            $table->string('event', 100)->index();
            $table->string('auditable_type', 191)->nullable()->index();
            $table->string('auditable_id', 64)->nullable()->index();

            // Descripción libre
            $table->string('description', 500)->nullable();

            // Cambios
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable(); // extra (ej: payload del request, etc.)

            // Contexto de request
            $table->string('ip_address', 64)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'auditable_comp_idx');
            $table->index(['event', 'created_at'], 'event_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
