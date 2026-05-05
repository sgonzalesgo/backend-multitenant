<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_user_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->uuid('student_id');
            $table->uuid('legal_representative_id')->nullable();
            $table->uuid('user_id')->nullable();

            $table->string('token', 120)->unique();

            $table->string('student_code', 80);
            $table->string('enrollment_code', 80)->nullable();

            $table->string('email');
            $table->string('status', 30)->default('pending');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('legal_representative_id')->references('id')->on('legal_representatives');
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'legal_representative_id']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
            $table->index(['token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_user_links');
    }
};
