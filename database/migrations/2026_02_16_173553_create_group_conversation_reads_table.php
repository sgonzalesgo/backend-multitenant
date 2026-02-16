<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_conversation_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->index();
            $table->uuid('group_id')->index();
            $table->uuid('user_id')->index();

            $table->timestamp('last_read_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['tenant_id', 'group_id', 'user_id'], 'uq_gcr_tenant_group_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_conversation_reads');
    }
};
