<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_students')->default(4)->after('level');
        });

        Schema::table('cohorts', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_students')->nullable()->after('seats');
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropColumn('min_students');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('min_students');
        });
    }
};
