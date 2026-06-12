<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            $table->uuid('support_ticket_comment_id')->nullable()->after('support_ticket_id');

            $table->foreign('support_ticket_comment_id')
                ->references('id')
                ->on('support_ticket_comments')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index('support_ticket_comment_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            $table->dropForeign(['support_ticket_comment_id']);
            $table->dropIndex(['support_ticket_comment_id']);
            $table->dropColumn('support_ticket_comment_id');
        });
    }
};
