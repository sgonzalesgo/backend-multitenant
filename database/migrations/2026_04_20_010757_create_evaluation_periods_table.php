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
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('code', 50)->unique();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('default_order')->default(1);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('default_order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
