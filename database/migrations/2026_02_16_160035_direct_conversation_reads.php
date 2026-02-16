<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('direct_conversation_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->index();
            $table->uuid('conversation_id')->index();
            $table->uuid('user_id')->index();

            // Marca hasta qué momento el usuario “leyó” esta conversación
            $table->timestamp('last_read_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['tenant_id', 'conversation_id', 'user_id'], 'uq_dm_reads_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_conversation_reads');
    }
};
