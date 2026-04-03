<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('person_id');
            $table->uuid('tenant_id');
            $table->string('code', 10)->unique();
            $table->string('academic_title')->nullable(); // Dr, MSc
            $table->string('academic_level')->nullable(); // licenciatura, maestría
            $table->string('specialty')->nullable();      // Matemáticas, Física// Ciencias, Humanidades
            $table->string('status')->default('active');
            $table->timestamp('status_changed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
