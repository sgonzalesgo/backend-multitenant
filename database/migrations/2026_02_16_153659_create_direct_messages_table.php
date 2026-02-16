<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('direct_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->index();
            $table->uuid('conversation_id')->index();

            $table->uuid('sender_id')->index();
            $table->text('body');

            $table->timestamps();

            // Si quieres FK reales y tu DB está lista para ello, descomenta:
             $table->foreign('conversation_id')->references('id')->on('direct_conversations')->cascadeOnDelete();
             $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_messages');
    }
};
