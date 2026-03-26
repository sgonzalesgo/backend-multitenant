<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: asegura gen_random_uuid()
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        // Default UUID para la PK
        DB::statement("
            ALTER TABLE direct_conversation_reads
            ALTER COLUMN id SET DEFAULT gen_random_uuid()
        ");

        // Índice único para poder hacer upsert por conversación/usuario
        Schema::table('direct_conversation_reads', function ($table) {
            $table->unique(
                ['tenant_id', 'conversation_id', 'user_id'],
                'direct_conversation_reads_tenant_conv_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('direct_conversation_reads', function ($table) {
            $table->dropUnique('direct_conversation_reads_tenant_conv_user_unique');
        });

        DB::statement("
            ALTER TABLE direct_conversation_reads
            ALTER COLUMN id DROP DEFAULT
        ");
    }
};
