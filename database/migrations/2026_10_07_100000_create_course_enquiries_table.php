<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email', 120);
            $table->string('contact', 80)->nullable();
            $table->string('language', 2);      // en | km
            $table->string('format', 10);       // online | in_person | either
            $table->string('level', 100);
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enquiries');
    }
};
