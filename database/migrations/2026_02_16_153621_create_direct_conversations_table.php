<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('direct_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->index();

            // Importante: siempre guardaremos los 2 user_ids en orden (user_one_id < user_two_id)
            $table->uuid('user_one_id')->index();
            $table->uuid('user_two_id')->index();

            $table->timestamps();

            $table->unique(['tenant_id', 'user_one_id', 'user_two_id'], 'uq_direct_conversations_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_conversations');
    }
};
