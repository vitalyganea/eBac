<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();             // bac-2027-s1
            $table->string('name');
            $table->string('exam_target', 32);            // bac-2027 | gimnaziu-2027
            $table->date('starts_at');
            $table->date('ends_at');
            $table->date('exam_starts_at')->nullable();   // data reală ANCE, pentru planul invers
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['exam_target', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
