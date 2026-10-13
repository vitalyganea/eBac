<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moderarea conținutului public generat de utilizatori.
     *
     * Public e doar ce apare altor elevi: **numele public** și **avatarul**.
     * Numele real și emailul nu ies niciodată din cont, deci nu se moderează.
     *
     * Strategia diferă pe cele două, pentru că sunt probleme diferite:
     *
     *   NUMELE se filtrează AUTOMAT, la creare și la schimbare. E text, deci
     *   regulile se pot scrie. Pre-moderarea ar bloca înscrierea, ceea ce omoară
     *   conversia pentru un cuvânt care în 99,9% din cazuri e inofensiv.
     *
     *   AVATARUL nu se poate filtra automat cu încredere. Rămâne raportarea plus
     *   o coadă de revizuire — problema apare rar, dar trebuie să se poată
     *   rezolva repede.
     */
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();

            // Polimorfic: acum utilizatori, mai târziu poate comentarii sau
            // itemi propuși de comunitate.
            $table->morphs('reportable');

            $table->string('subject_type', 24);   // avatar | username
            $table->string('reason', 32);         // ofensator | impersonare | contact | spam | altul
            $table->text('comment')->nullable();

            // Nullable: raportarea trebuie să funcționeze și dacă raportorul își
            // șterge ulterior contul — semnalarea rămâne validă.
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 16)->default('open'); // open | resolved | dismissed
            $table->string('action_taken', 32)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['reportable_type', 'reportable_id', 'subject_type']);

            // Un utilizator nu poate raporta același lucru de mai multe ori —
            // altfel un grup mic poate face o persoană să pară masiv raportată.
            $table->unique(['reportable_type', 'reportable_id', 'subject_type', 'reported_by'], 'content_reports_unique_reporter');
        });

        Schema::table('users', function (Blueprint $table) {
            // Ridicat de moderator: numele a fost respins, iar elevul trebuie să
            // aleagă altul înainte de a continua. Nu îi ștergem contul și nu îi
            // pierdem progresul pentru un nume nepotrivit.
            $table->boolean('username_reset_required')->default(false)->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username_reset_required');
        });

        Schema::dropIfExists('content_reports');
    }
};
