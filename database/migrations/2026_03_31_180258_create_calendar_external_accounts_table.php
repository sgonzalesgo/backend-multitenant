<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_external_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('user_id');

            $table->string('provider', 30); // google
            $table->string('provider_account_email', 255)->nullable();

            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestampTz('token_expires_at')->nullable();

            $table->jsonb('scopes')->nullable();

            $table->boolean('sync_enabled')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('provider');
            $table->index('sync_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_external_accounts');
    }
};
