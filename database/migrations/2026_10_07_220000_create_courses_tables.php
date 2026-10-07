<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->unsignedSmallInteger('hours')->default(0);
            $table->json('languages')->nullable();
            $table->string('level', 100)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('title');
            $table->unsignedSmallInteger('hours')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('schedule_text')->nullable();
            $table->string('format', 20)->default('online'); // online|in_person|hybrid
            $table->unsignedSmallInteger('seats')->default(10);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 8)->default('USD');
            $table->string('status', 20)->default('draft')->index(); // draft|open|full|closed
            $table->timestamps();
        });

        Schema::table('course_enquiries', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('admin_note')->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
            $table->dropConstrainedForeignId('course_id');
        });
        Schema::dropIfExists('cohorts');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
