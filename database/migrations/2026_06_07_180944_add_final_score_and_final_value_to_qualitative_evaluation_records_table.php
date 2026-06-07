<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualitative_evaluation_records', function (Blueprint $table) {
            $table->decimal('final_score', 5, 2)
                ->nullable()
                ->after('enrollment_id');

            $table->string('final_value', 10)
                ->nullable()
                ->after('final_score');
        });
    }

    public function down(): void
    {
        Schema::table('qualitative_evaluation_records', function (Blueprint $table) {
            $table->dropColumn([
                'final_score',
                'final_value',
            ]);
        });
    }
};
