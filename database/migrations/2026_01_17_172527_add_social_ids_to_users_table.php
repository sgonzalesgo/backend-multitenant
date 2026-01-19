<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Si tienes usuarios con is_active null, cambia a true
            DB::table('users')->whereNull('is_active')->update(['is_active' => true]);

            // Agregar columnas
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('facebook_id')->nullable()->unique()->after('google_id');

            // Solo si vas a tratar instagram como provider distinto:
            $table->string('instagram_id')->nullable()->unique()->after('facebook_id');

            // Recomendado: is_active con default true (hoy es nullable)
            $table->boolean('is_active')->default(true)->change();

            // opcional: agregar locale
            $table->string('locale', 10)->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['facebook_id']);
            $table->dropUnique(['instagram_id']);

            $table->dropColumn(['google_id', 'facebook_id', 'instagram_id']);

            // opcional: volver a nullable si quieres
            $table->boolean('is_active')->nullable()->change();
        });
    }
};
