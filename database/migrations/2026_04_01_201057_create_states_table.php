<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_id')
                ->constrained('countries')
                ->cascadeOnDelete();

            $table->string('code', 50); // AMAZONAS, PICHINCHA
            $table->string('name', 150);

            $table->timestamps();

            // 🔥 CLAVE IMPORTANTE
            $table->unique(['country_id', 'code']);

            // evita duplicados por nombre dentro del país
            $table->unique(['country_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
