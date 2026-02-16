<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->index();
            $table->uuid('group_id')->index();
            $table->uuid('user_id')->index(); // 👈 OJO: user_id (no sender_id)

            $table->text('body');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // ✅ clave para sidebar / unread
            $table->index(['tenant_id', 'group_id', 'created_at'], 'idx_gm_tenant_group_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_messages');
    }
};
