<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('support_ticket_id');

            $table->foreign('support_ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->uuid('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->text('comment');

            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['support_ticket_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_comments');
    }
};
