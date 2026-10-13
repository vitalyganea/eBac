<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabelele de import/export ale Filament (publicate din filament/actions).
 *
 * Nu folosim încă importuri sau exporturi, dar Filament înregistrează oricum
 * rutele `filament/exports/{export}/download` și
 * `filament/imports/{import}/failed-rows/download`. Fără tabele, oricine —
 * inclusiv un vizitator anonim — obținea un 500 din SQL pe aceste URL-uri.
 * Cu ele, răspunsul corect e 403/404.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
