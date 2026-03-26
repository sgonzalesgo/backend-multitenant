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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('user_id')->index();

            $table->string('type', 120)->index();
            $table->string('title', 255);
            $table->text('message');

            $table->string('module', 80)->nullable()->index();
            $table->string('route', 500)->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('read_at')->nullable()->index();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
