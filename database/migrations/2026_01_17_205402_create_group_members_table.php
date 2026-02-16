<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->uuid('tenant_id')->index();

            $table->uuid('group_id');
            $table->uuid('user_id');

            $table->string('status')->default('invited'); // invited | accepted
            $table->uuid('invited_by')->nullable();

            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->primary(['group_id', 'user_id']);

            $table->index(['tenant_id', 'group_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
