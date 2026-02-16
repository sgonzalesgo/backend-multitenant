<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // direct_messages: acelerar listados por conversación + unread_count
        Schema::table('direct_messages', function (Blueprint $table) {
            // Para queries: WHERE tenant_id AND conversation_id ORDER BY created_at
            $table->index(['tenant_id', 'conversation_id', 'created_at'], 'idx_dm_tenant_conv_created');

            // Para unread_count: WHERE tenant_id AND conversation_id AND sender_id <> ? AND created_at > ?
            $table->index(['tenant_id', 'conversation_id', 'sender_id', 'created_at'], 'idx_dm_tenant_conv_sender_created');
        });

        // direct_conversation_reads: acelerar join por (tenant, user) y conversación
        Schema::table('direct_conversation_reads', function (Blueprint $table) {
            // Para subquery/joins: WHERE tenant_id AND user_id
            $table->index(['tenant_id', 'user_id'], 'idx_dcr_tenant_user');

            // Para buscar rápido el read state específico
            $table->index(['tenant_id', 'conversation_id', 'user_id'], 'idx_dcr_tenant_conv_user');
        });

        // direct_conversations: acelerar listado "mis conversaciones"
        Schema::table('direct_conversations', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_one_id'], 'idx_dc_tenant_user_one');
            $table->index(['tenant_id', 'user_two_id'], 'idx_dc_tenant_user_two');

            // Ya existe unique(tenant_id, user_one_id, user_two_id)
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('idx_dm_tenant_conv_created');
            $table->dropIndex('idx_dm_tenant_conv_sender_created');
        });

        Schema::table('direct_conversation_reads', function (Blueprint $table) {
            $table->dropIndex('idx_dcr_tenant_user');
            $table->dropIndex('idx_dcr_tenant_conv_user');
        });

        Schema::table('direct_conversations', function (Blueprint $table) {
            $table->dropIndex('idx_dc_tenant_user_one');
            $table->dropIndex('idx_dc_tenant_user_two');
        });
    }
};
