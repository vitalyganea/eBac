<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disciplinele pe care elevul se pregătește — alese EXPLICIT de el.
     *
     * Bacalaureatul e din mai multe discipline, iar `users.profile` nu are voie
     * să fie o poartă de acces: profilul spune doar ce e obligatoriu și ce e la
     * alegere PENTRU EL, ca ghidaj. Un elev de la real care vrea să exerseze
     * istorie trebuie să poată, oricând, fără să-și schimbe profilul.
     *
     * Selecția e persistentă și reordonabilă, nu o alegere unică la înscriere.
     */
    public function up(): void
    {
        Schema::create('user_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            // Disciplina de profil a elevului: apare prima și e ținta implicită
            // a provocării zilnice.
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'subject_id']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subjects');
    }
};
