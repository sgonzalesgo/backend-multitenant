<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parallels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unique(['tenant_id', 'code'], 'parallels_tenant_code_unique');
            $table->unique(['tenant_id', 'name'], 'parallels_tenant_name_unique');
            $table->index(['tenant_id', 'is_active'], 'parallels_tenant_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parallels');
    }
};
