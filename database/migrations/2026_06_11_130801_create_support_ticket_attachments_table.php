<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('support_ticket_id');

            $table->foreign('support_ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->uuid('uploaded_by_id');

            $table->foreign('uploaded_by_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->timestamps();

            $table->index('support_ticket_id');
            $table->index('uploaded_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};
