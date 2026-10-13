<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Autentificarea cu Google.
 *
 * Legăturile stau într-un tabel separat, nu în coloane pe `users`, ca al doilea
 * furnizor — dacă apare vreodată Apple, obligatoriu în App Store — să nu ceară
 * altă migrație și alte două coloane nefolosite de majoritatea conturilor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 32);
            $table->string('provider_id');

            // Emailul de la furnizor se reține separat de `users.email`: elevul
            // și-l poate schimba pe al doilea, iar la următoarea autentificare
            // trebuie să știm după ce l-am recunoscut.
            $table->string('provider_email')->nullable();

            $table->timestamp('linked_at');
            $table->timestamps();

            // Un cont Google se leagă la un singur utilizator, iar un utilizator
            // are o singură identitate per furnizor.
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Conturile create prin Google nu au parolă. Nullable, nu șir gol:
            // `Hash::check` tratează null explicit, iar „fără parolă" trebuie să
            // fie o stare vizibilă, nu una ghicită dintr-un hash imposibil.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
