<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_id_maps', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('entity');
            $table->string('old_id');
            $table->uuid('new_id');

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['entity', 'old_id']);
            $table->index(['entity']);
            $table->index(['new_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_id_maps');
    }
};
