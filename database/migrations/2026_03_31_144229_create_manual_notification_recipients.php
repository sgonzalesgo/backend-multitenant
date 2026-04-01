<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manual_notification_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('manual_notification_id');
            $table->uuid('user_id');
            $table->uuid('notification_id')->nullable();

            $table->timestamps();

            // Relaciones
            $table->foreign('manual_notification_id')
                ->references('id')
                ->on('manual_notifications')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('notification_id')
                ->references('id')
                ->on('notifications')
                ->nullOnDelete();

            // Índices importantes
            $table->index('manual_notification_id');
            $table->index('user_id');
            $table->index('notification_id');

            // Evitar duplicados (mismo envío + mismo usuario)
            $table->unique(['manual_notification_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_notification_recipients');
    }
};
