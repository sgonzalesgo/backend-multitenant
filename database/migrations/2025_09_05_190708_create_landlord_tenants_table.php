<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('name');
            $t->string('domain')->unique();

            // Campos base
            $t->string('name');
            $t->string('logo')->nullable();
            $t->string('address')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();

            // Identificación legal
            $t->uuid('legal_id')->nullable();
            $t->string('legal_id_type')->nullable();
            $t->boolean('is_active')->default(true);

            // Campos nuevos
            $t->string('business_name')->nullable();             // Razón social
            $t->string('campus_logo')->nullable();               // Logo del plantel
            $t->string('campus_type')->nullable();               // Tipo de plantel
            $t->string('slogan')->nullable();                    // Lema / Eslogan
            $t->string('amie_code')->nullable();                 // AMIE
            $t->string('city')->nullable();
            $t->string('state')->nullable();
            $t->string('country')->nullable();
            $t->string('country_logo')->nullable();              // Logo del país
            $t->boolean('country_logo_position_right')->default(false); // true = derecha
            $t->string('zip')->nullable();

            $t->timestamps();

            $t->index(['name']);
            $t->index(['is_active']);
        });
    }
};
