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
        Schema::create('manual_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('created_by');

            $table->string('title', 120);
            $table->text('message');
            $table->string('route', 255)->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(['tenant_id', 'archived_at']);
            $table->index(['tenant_id', 'created_by']);
            $table->index(['tenant_id', 'sent_at']);
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_notifications');
    }
};
