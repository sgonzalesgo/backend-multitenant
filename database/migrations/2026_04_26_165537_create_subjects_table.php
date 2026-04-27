<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('subject_type_id')->nullable();
            $table->uuid('evaluation_type_id')->nullable();

            $table->string('code', 3);
            $table->string('name', 100);
            $table->string('description', 255)->nullable();

            $table->boolean('is_average')->default(true);
            $table->boolean('is_behavior')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            $table->foreign('subject_type_id')
                ->references('id')
                ->on('subject_types')
                ->nullOnDelete();

            $table->foreign('evaluation_type_id')
                ->references('id')
                ->on('evaluation_types')
                ->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['tenant_id', 'name']);

            $table->index(['tenant_id', 'subject_type_id']);
            $table->index(['tenant_id', 'evaluation_type_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_average']);
            $table->index(['tenant_id', 'is_behavior']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
