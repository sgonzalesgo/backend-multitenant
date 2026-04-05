<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_positions', function (Blueprint $t) {
            $t->uuid('id')->primary();

            $t->uuid('tenant_id');
            $t->uuid('person_id');
            $t->uuid('position_id');

            $t->string('signature')->nullable();

            $t->boolean('is_active')->default(true);

            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();

            $t->timestamps();

            $t->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $t->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->restrictOnDelete();

            $t->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->restrictOnDelete();

            $t->index(['tenant_id']);
            $t->index(['person_id']);
            $t->index(['position_id']);
            $t->index(['is_active']);

            $t->unique(['tenant_id', 'person_id', 'position_id'], 'tenant_positions_unique_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_positions');
    }
};
