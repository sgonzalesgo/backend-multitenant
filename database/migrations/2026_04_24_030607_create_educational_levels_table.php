<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 100);

            $table->unsignedInteger('sort_order');

            $table->unsignedInteger('start_number');
            $table->unsignedInteger('end_number');

            $table->boolean('has_specialty')->default(false);

            // 👇 SOLO definimos la columna aquí
            $table->uuid('next_educational_level_id')->nullable();

            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code', 'deleted_at']);
            $table->unique(['tenant_id', 'name', 'deleted_at']);
            $table->unique(['tenant_id', 'sort_order', 'deleted_at']);
        });

        // 👇 AQUÍ agregamos la FK (CLAVE)
        Schema::table('educational_levels', function (Blueprint $table) {
            $table->foreign('next_educational_level_id')
                ->references('id')
                ->on('educational_levels')
                ->nullOnDelete();
        });

        // 👇 CHECK constraint
        DB::statement('
            ALTER TABLE educational_levels
            ADD CONSTRAINT educational_levels_range_check
            CHECK (start_number <= end_number)
        ');
    }

    public function down(): void
    {
        Schema::table('educational_levels', function (Blueprint $table) {
            $table->dropForeign(['next_educational_level_id']);
        });

        Schema::dropIfExists('educational_levels');
    }
};
