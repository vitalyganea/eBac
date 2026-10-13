<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('locality')->nullable();
            $table->string('district')->nullable();       // raion
            $table->string('type', 32)->nullable();       // liceu | colegiu | centru_excelenta
            $table->string('language', 8)->default('ro'); // limba de predare
            $table->boolean('is_verified')->default(false);
            $table->unsignedInteger('students_count')->default(0);
            $table->timestamps();

            $table->index(['district', 'locality']);
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
