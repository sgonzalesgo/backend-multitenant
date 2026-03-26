<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        DB::statement("
            ALTER TABLE group_conversation_reads
            ALTER COLUMN id SET DEFAULT gen_random_uuid()
        ");

        Schema::table('group_conversation_reads', function ($table) {
            $table->unique(
                ['tenant_id', 'group_id', 'user_id'],
                'group_conversation_reads_tenant_group_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('group_conversation_reads', function ($table) {
            $table->dropUnique('group_conversation_reads_tenant_group_user_unique');
        });

        DB::statement("
            ALTER TABLE group_conversation_reads
            ALTER COLUMN id DROP DEFAULT
        ");
    }
};
