<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('user_id');

            $table->index('tenant_id', 'oauth_access_tokens_tenant_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropIndex('oauth_access_tokens_tenant_id_index');
            $table->dropColumn('tenant_id');
        });
    }
};
