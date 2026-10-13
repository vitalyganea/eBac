<?php

use App\Domain\Attempts\SubmitAnswer;
use App\Domain\Attempts\SubmitAnswerData;
use App\Domain\Leaderboards\Leaderboard;
use App\Domain\Xp\Level;
use App\Models\Item;
use App\Models\Subject;
use App\Models\SubjectStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    // Testele rulează pe indexul Redis 15 (vezi phpunit.xml), dar sorted sets-urile
    // persistă între teste — le curățăm ca pozițiile să fie deterministe.
    Redis::flushdb();

    $this->matematica = Subject::where('slug', 'matematica')->firstOrFail();
    $this->engleza = Subject::where('slug', 'limba-engleza')->firstOrFail();

    $this->answer = function (User $user, Item $item): void {
        $key = $item->gradingKey();

        $answer = match (true) {
            isset($key['correct']) => ['choice' => $key['correct'][0]],
            isset($key['order']) => ['order' => $key['order']],
            isset($key['pairs']) => ['pairs' => $key['pairs']],
            isset($key['values']) => ['values' => array_map(fn ($v) => is_array($v) ? $v[0] : $v, $key['values'])],
            array_key_exists('value', $key) => ['value' => (string) $key['value']],
            default => [],
        };

        app(SubmitAnswer::class)($user, new SubmitAnswerData(
            clientUuid: (string) Str::uuid(),
            itemId: $item->id,
            answer: $answer,
        ));
    };
});

it('include utilizatorii în clasamente implicit', function () {
    $user = User::factory()->create();

    expect($user->leaderboard_opt_in)->toBeTrue();
});

it('contorizează XP separat pe fiecare disciplină', function () {
    $user = User::factory()->create();

    $mate = Item::practicePool()->where('subject_id', $this->matematica->id)->first();
    $eng = Item::practicePool()->where('subject_id', $this->engleza->id)->first();

    ($this->answer)($user, $mate);
    ($this->answer)($user, $eng);

    $stats = SubjectStat::where('user_id', $user->id)->get()->keyBy('subject_id');

    expect($stats)->toHaveCount(2)
        ->and($stats[$this->matematica->id]->xp)->toBeGreaterThan(0)
        ->and($stats[$this->engleza->id]->xp)->toBeGreaterThan(0)
        // XP-ul de la engleză nu se amestecă în cel de la matematică.
        ->and($stats[$this->matematica->id]->attempts_count)->toBe(1)
        ->and($stats[$this->engleza->id]->attempts_count)->toBe(1);
});

/**
 * Cerința: „la mate sunt al 34-lea din 938".
 */
it('raportează poziția și totalul pe disciplină', function () {
    $leaderboard = app(Leaderboard::class);
    $items = Item::practicePool()->where('subject_id', $this->matematica->id)->take(3)->get();

    // Trei elevi cu volume diferite de activitate.
    $mult = User::factory()->create();
    $mediu = User::factory()->create();
    $putin = User::factory()->create();

    foreach ($items as $item) {
        ($this->answer)($mult, $item);
    }
    foreach ($items->take(2) as $item) {
        ($this->answer)($mediu, $item);
    }
    ($this->answer)($putin, $items->first());

    $r1 = $leaderboard->subjectRank($mult, $this->matematica->id);
    $r2 = $leaderboard->subjectRank($mediu, $this->matematica->id);
    $r3 = $leaderboard->subjectRank($putin, $this->matematica->id);

    expect($r1['rank'])->toBe(1)
        ->and($r2['rank'])->toBe(2)
        ->and($r3['rank'])->toBe(3)
        // Totalul e același pentru toți: numărul de participanți la disciplină.
        ->and($r1['total'])->toBe(3)
        ->and($r3['total'])->toBe(3)
        ->and($r1['xp'])->toBeGreaterThan($r3['xp']);
});

it('nu clasează un elev la o disciplină la care nu a răspuns', function () {
    $user = User::factory()->create();
    $mate = Item::practicePool()->where('subject_id', $this->matematica->id)->first();

    ($this->answer)($user, $mate);

    $leaderboard = app(Leaderboard::class);

    expect($leaderboard->subjectRank($user, $this->matematica->id))->not->toBeNull()
        // Nu îl arătăm „ultimul din N" înainte să fi încercat ceva.
        ->and($leaderboard->subjectRank($user, $this->engleza->id))->toBeNull();
});

it('clasamentele sunt independente între discipline', function () {
    $leaderboard = app(Leaderboard::class);

    $matematician = User::factory()->create();
    $anglist = User::factory()->create();

    foreach (Item::practicePool()->where('subject_id', $this->matematica->id)->take(3)->get() as $i) {
        ($this->answer)($matematician, $i);
    }
    foreach (Item::practicePool()->where('subject_id', $this->engleza->id)->take(3)->get() as $i) {
        ($this->answer)($anglist, $i);
    }

    // Fiecare e primul la disciplina lui și neclasat la cealaltă.
    expect($leaderboard->subjectRank($matematician, $this->matematica->id)['rank'])->toBe(1)
        ->and($leaderboard->subjectRank($matematician, $this->engleza->id))->toBeNull()
        ->and($leaderboard->subjectRank($anglist, $this->engleza->id)['rank'])->toBe(1)
        ->and($leaderboard->subjectRank($anglist, $this->matematica->id))->toBeNull();
});

it('exclude din clasament utilizatorii care s-au retras', function () {
    $inclus = User::factory()->create();
    $retras = User::factory()->create(['leaderboard_opt_in' => false]);

    $item = Item::practicePool()->where('subject_id', $this->matematica->id)->first();
    ($this->answer)($inclus, $item);
    ($this->answer)($retras, $item);

    $leaderboard = app(Leaderboard::class);

    expect($leaderboard->subjectRank($inclus, $this->matematica->id)['total'])->toBe(1)
        ->and($leaderboard->subjectRank($retras, $this->matematica->id))->toBeNull();

    // Dar progresul lui se contorizează în continuare — retragerea din clasament
    // nu înseamnă oprirea învățării.
    expect(SubjectStat::where('user_id', $retras->id)->first()->xp)->toBeGreaterThan(0);
});

/**
 * Redis e cache, nu sursă de adevăr. Se poate goli oricând — la repornire, la
 * evacuare din memorie, la o greșeală de operare.
 */
it('reconstruiește clasamentul din baza de date după un flush de Redis', function () {
    $leaderboard = app(Leaderboard::class);
    $items = Item::practicePool()->where('subject_id', $this->matematica->id)->take(2)->get();

    $primul = User::factory()->create();
    $doilea = User::factory()->create();

    foreach ($items as $i) {
        ($this->answer)($primul, $i);
    }
    ($this->answer)($doilea, $items->first());

    Redis::flushdb();
    expect($leaderboard->subjectRank($primul, $this->matematica->id))->toBeNull();

    $refacuti = $leaderboard->rebuildSubject($this->matematica->id);

    expect($refacuti)->toBe(2)
        ->and($leaderboard->subjectRank($primul, $this->matematica->id)['rank'])->toBe(1)
        ->and($leaderboard->subjectRank($doilea, $this->matematica->id)['rank'])->toBe(2);
});

it('panoul afișează poziția pe fiecare disciplină', function () {
    $user = User::factory()->create();
    $user->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    ($this->answer)($user, Item::practicePool()->where('subject_id', $this->matematica->id)->first());

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('subjects.0.rank.rank', 1)
            ->where('subjects.0.rank.total', 1)
            ->has('subjects.0.xp')
        );
});

it('nu mai există tabelul schools', function () {
    // Platforma nu presupune apartenența la o instituție: pot fi candidați care
    // repetă examenul sau care învață pe cont propriu.
    expect(Schema::hasTable('schools'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'school_id'))->toBeFalse();
});

it('separă XP-ul și nivelul pe discipline', function () {
    $user = User::factory()->create();

    // Trei răspunsuri la matematică, unul la engleză.
    foreach (Item::practicePool()->where('subject_id', $this->matematica->id)->take(3)->get() as $i) {
        ($this->answer)($user, $i);
    }
    ($this->answer)($user, Item::practicePool()->where('subject_id', $this->engleza->id)->first());

    $stats = SubjectStat::where('user_id', $user->id)->get()->keyBy('subject_id');

    $mateXp = $stats[$this->matematica->id]->xp;
    $engXp = $stats[$this->engleza->id]->xp;

    // XP-ul e separat, iar nivelul se derivă din XP-ul DISCIPLINEI, nu din total.
    expect($mateXp)->toBeGreaterThan($engXp)
        ->and($stats[$this->matematica->id]->level())->toBe(Level::forXp($mateXp))
        ->and($stats[$this->engleza->id]->level())->toBe(Level::forXp($engXp))
        // Totalul global rămâne suma lor — util pentru profil, nu pentru comparație.
        ->and($user->refresh()->xp)->toBe($mateXp + $engXp);
});

it('hub-ul disciplinei arată nivel, poziție și cele trei zone', function () {
    $user = User::factory()->create();
    $user->subjects()->attach($this->matematica->id, [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    ($this->answer)($user, Item::practicePool()->where('subject_id', $this->matematica->id)->first());

    $this->actingAs($user)->get(route('subject.show', 'matematica'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Subjects/Show')
            ->has('progress.level')
            ->has('progress.percent')
            ->where('rank.rank', 1)
            // Ambele zone sunt declarate explicit, cu numărul de lecții/itemi.
            ->has('sections.course.available')
            ->where('sections.tests.available', true)
        );
});

it('nu dă acces la hub-ul unei discipline nealese', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('subject.show', 'matematica'))->assertNotFound();
});

it('clasamentul afișabil include rândul propriu când elevul e în afara primilor', function () {
    $leaderboard = app(Leaderboard::class);
    $items = Item::practicePool()->where('subject_id', $this->matematica->id)->take(3)->get();

    // XP distinct pentru fiecare: la scor egal, ordinea în sorted set e
    // arbitrară, iar testul ar deveni instabil.
    $primul = User::factory()->create();
    foreach ($items as $i) {
        ($this->answer)($primul, $i);
    }

    $aldoilea = User::factory()->create();
    foreach ($items->take(2) as $i) {
        ($this->answer)($aldoilea, $i);
    }

    $codas = User::factory()->create();
    ($this->answer)($codas, $items->first());

    // Cu limita 2, codașul nu intră în listă — dar trebuie să se vadă pe sine.
    $board = $leaderboard->subjectBoard($codas, $this->matematica->id, limit: 2);

    expect($board['entries'])->toHaveCount(2)
        ->and($board['total'])->toBe(3)
        ->and($board['me'])->not->toBeNull()
        ->and($board['me']['rank'])->toBe(3)
        ->and($board['me']['is_me'])->toBeTrue();

    // Iar cel de pe locul 1, deja în listă, nu apare de două ori.
    $boardPrimul = $leaderboard->subjectBoard($primul, $this->matematica->id, limit: 2);
    expect($boardPrimul['me'])->toBeNull()
        ->and(collect($boardPrimul['entries'])->firstWhere('is_me', true))->not->toBeNull();
});

it('supraviețuiește unui membru Redis căruia nu-i mai corespunde niciun cont', function () {
    // Redis e cache, nu sursă de adevăr: rămâne în urmă după ștergerea unui cont
    // prin PurgeUserData sau după un `migrate:fresh` peste un Redis care persistă.
    // Înainte, `$users[$id]` pe Collection dădea „Undefined array key" — deci 500
    // pe /disciplina/{slug} pentru TOȚI elevii, nu doar pentru cel dispărut.
    $leaderboard = app(Leaderboard::class);
    $item = Item::practicePool()->where('subject_id', $this->matematica->id)->first();

    $elev = User::factory()->create();
    ($this->answer)($elev, $item);

    $fantoma = User::factory()->create();
    $leaderboard->addXp($fantoma, 5_000, $this->matematica->id);
    $fantoma->delete();

    $board = $leaderboard->subjectBoard($elev, $this->matematica->id);

    expect($board['entries'])->toHaveCount(1)
        ->and(array_is_list($board['entries']))->toBeTrue()
        ->and($board['entries'][0]['is_me'])->toBeTrue()
        ->and($board['entries'][0]['username'])->toBe($elev->publicName());
});

it('curăță contul dispărut la reconstruirea clasamentului', function () {
    $leaderboard = app(Leaderboard::class);
    $item = Item::practicePool()->where('subject_id', $this->matematica->id)->first();

    $elev = User::factory()->create();
    ($this->answer)($elev, $item);

    $fantoma = User::factory()->create();
    $leaderboard->addXp($fantoma, 5_000, $this->matematica->id);
    $fantoma->delete();

    $leaderboard->rebuildSubject($this->matematica->id);

    $board = $leaderboard->subjectBoard($elev, $this->matematica->id);

    // Fantoma umfla și totalul, nu doar lista: „al 2-lea din 2" era fals.
    expect($board['total'])->toBe(1)
        ->and($board['entries'])->toHaveCount(1);
});
