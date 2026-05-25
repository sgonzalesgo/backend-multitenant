<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->boolean('allow_attendance')
                ->default(true)
                ->after('end_date');

            $table->boolean('allow_grades')
                ->default(true)
                ->after('allow_attendance');

            $table->index('allow_attendance');
            $table->index('allow_grades');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->dropIndex(['allow_attendance']);
            $table->dropIndex(['allow_grades']);

            $table->dropColumn([
                'allow_attendance',
                'allow_grades',
            ]);
        });
    }
};
