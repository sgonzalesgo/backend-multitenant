<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('full_name');
            $table->string('photo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->text('address')->nullable();
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();

            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete();

            $table->string('zip')->nullable();

            $table->string('legal_id');
            $table->string('legal_id_type');

            $table->date('birthday')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->string('nationality')->nullable();

            $table->timestamp('deceased_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['legal_id_type', 'legal_id'], 'persons_legal_unique');
            $table->index('full_name');
            $table->index('email');
            $table->index('phone');
            $table->index('country_id');
            $table->index('state_id');
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
