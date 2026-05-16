<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_level_specialty', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('educational_level_id')
                ->constrained('educational_levels')
                ->cascadeOnDelete();

            $table->foreignUuid('specialty_id')
                ->constrained('specialties')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'educational_level_id',
                'specialty_id',
            ], 'edu_level_specialty_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_level_specialty');
    }
};
