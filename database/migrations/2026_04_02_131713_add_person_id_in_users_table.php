<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar status y person_id
        Schema::table('users', function (Blueprint $table) {

            $table->uuid('person_id')->nullable()->after('id');
            $table->unique('person_id');
            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {

        // 1. Eliminar relación y status
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropUnique(['person_id']);
            $table->dropColumn(['person_id', 'status']);
        });
    }
};
