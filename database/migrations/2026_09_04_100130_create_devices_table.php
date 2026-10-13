<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dispozitivele utilizatorului.
     *
     * Există de acum, deși aplicația mobilă vine mai târziu, pentru două motive:
     *   1. Notificările push (FCM/APNs) — motorul principal de retenție al
     *      streak-ului, și adevăratul argument pentru a construi aplicația.
     *   2. Revocarea sesiunilor: elevul își pierde telefonul, îi tăiem accesul
     *      fără să-i schimbăm parola.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();          // generat de client, stabil între reinstalări
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 16);          // web | ios | android
            $table->string('name')->nullable();      // „iPhone 14 · Vitalie"
            $table->text('push_token')->nullable();
            $table->string('push_provider', 16)->nullable();  // fcm | apns | webpush
            $table->string('app_version', 24)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->string('locale', 8)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
            $table->index('push_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
