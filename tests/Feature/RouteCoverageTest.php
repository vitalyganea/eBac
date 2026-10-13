<?php

use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\Item;
use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->user = User::factory()->create(['password' => Hash::make('parola-sigura-123')]);
    $this->mate = Subject::where('slug', 'matematica')->firstOrFail();

    foreach (Subject::all() as $i => $subject) {
        $this->user->subjects()->attach($subject->id, [
            'is_primary' => $i === 0,
            'sort_order' => $i,
            'added_at' => now(),
        ]);
    }

    // Antetul care transformă cererea într-una Inertia. Fără el, testele ar
    // verifica o cale pe care browserul nu o folosește niciodată din aplicație.
    $this->inertia = ['X-Inertia' => 'true'];

    // În teste nu există manifest de build, deci Inertia răspunde 409 („altă
    // versiune de assets") și anunță versiunea corectă în antet. O aflăm de la
    // server și repetăm cererea, exact ca browserul.
    $this->inertiaGet = function (string $url) {
        $raspuns = $this->actingAs($this->user)->get($url, $this->inertia);

        if ($raspuns->status() === 409) {
            $raspuns = $this->actingAs($this->user)->get($url, $this->inertia + [
                'X-Inertia-Version' => $raspuns->headers->get('X-Inertia-Version'),
            ]);
        }

        return $raspuns;
    };
});

/*
|--------------------------------------------------------------------------
| Zona publică — Blade, fără JavaScript (§14)
|--------------------------------------------------------------------------
*/

it('servește zona publică vizitatorului anonim', function () {
    $skill = Skill::where('subject_id', $this->mate->id)->firstOrFail();

    $this->get(route('public.home'))->assertOk();
    $this->get(route('public.sitemap'))->assertOk()
        ->assertHeader('content-type', 'application/xml; charset=utf-8');
    $this->get(route('public.exam', 2027))->assertOk();
    $this->get(route('public.subject', $this->mate->slug))->assertOk();
    $this->get(route('public.skill', [$this->mate->slug, $skill->slug]))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Porți: cine are voie unde
|--------------------------------------------------------------------------
*/

it('trimite vizitatorul anonim spre autentificare pe fiecare rută a aplicației', function () {
    $doarAutentificat = [
        ['get', route('dashboard')],
        ['get', route('gaps.index')],
        ['get', route('settings.edit')],
        ['get', route('subjects.edit')],
        ['get', route('subject.show', $this->mate->slug)],
        ['get', route('course.index', $this->mate->slug)],
        ['post', route('logout')],
        ['post', route('report.store')],
        ['post', route('gaps.store')],
        ['put', route('settings.update')],
        ['put', route('settings.password')],
        ['put', route('subjects.update')],
        ['delete', route('settings.destroy')],
        ['delete', route('settings.avatar.destroy')],
        ['post', route('practice.start')],
    ];

    foreach ($doarAutentificat as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
});

it('trimite elevul autentificat departe de paginile de autentificare', function () {
    foreach ([route('login'), route('register')] as $url) {
        $this->actingAs($this->user)->get($url)->assertRedirect(route('dashboard'));
    }
});

/*
|--------------------------------------------------------------------------
| Paginile aplicației
|--------------------------------------------------------------------------
*/

it('randează fiecare pagină a aplicației ca răspuns Inertia', function () {
    $skill = Skill::whereHas('lesson', fn ($q) => $q->where('status', 'published'))->firstOrFail();
    $subject = Subject::findOrFail($skill->subject_id);

    $pagini = [
        route('dashboard') => 'Dashboard',
        route('gaps.index') => 'Gaps/Index',
        route('settings.edit') => 'Settings/Edit',
        route('subjects.edit') => 'Subjects/Edit',
        route('subject.show', $this->mate->slug) => 'Subjects/Show',
        route('course.index', $this->mate->slug) => 'Course/Index',
        route('course.show', [$subject->slug, $skill->slug]) => 'Course/Show',
    ];

    foreach ($pagini as $url => $componenta) {
        ($this->inertiaGet)($url)
            ->assertOk()
            ->assertJsonPath('component', $componenta);
    }
});

/*
|--------------------------------------------------------------------------
| Scrierile
|--------------------------------------------------------------------------
*/

it('acceptă alegerea disciplinelor', function () {
    $this->actingAs($this->user)
        ->from(route('subjects.edit'))
        ->put(route('subjects.update'), [
            'subject_ids' => [$this->mate->id],
            'primary_id' => $this->mate->id,
        ], $this->inertia)
        ->assertRedirect(route('dashboard'));
});

it('acceptă salvarea setărilor, a parolei și a avatarului', function () {
    $this->actingAs($this->user)
        ->from(route('settings.edit'))
        ->put(route('settings.update'), [
            'username' => 'elev_nou_2027',
            'timezone' => 'Europe/Chisinau',
            'leaderboard_opt_in' => true,
        ], $this->inertia)
        ->assertRedirect(route('settings.edit'));

    $this->actingAs($this->user)
        ->from(route('settings.edit'))
        ->put(route('settings.password'), [
            'current_password' => 'parola-sigura-123',
            'password' => 'alta-parola-sigura-456',
            'password_confirmation' => 'alta-parola-sigura-456',
        ], $this->inertia)
        ->assertRedirect(route('settings.edit'));

    $this->actingAs($this->user)
        ->from(route('settings.edit'))
        ->post(route('settings.avatar'), [
            'avatar' => UploadedFile::fake()->image('avatar.png', 256, 256),
        ], $this->inertia)
        ->assertRedirect(route('settings.edit'));

    $this->actingAs($this->user)
        ->from(route('settings.edit'))
        ->delete(route('settings.avatar.destroy'), [], $this->inertia)
        ->assertRedirect(route('settings.edit'));
});

it('acceptă semnalarea altui utilizator', function () {
    $tinta = User::factory()->create(['username' => 'tinta_raport']);

    $this->actingAs($this->user)
        ->from(route('dashboard'))
        ->post(route('report.store'), [
            'username' => $tinta->username,
            'subject' => 'username',
            'reason' => 'ofensator',
        ], $this->inertia)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    // Semnalarea unui avatar inexistent e respinsă, dar tot prin `back()` cu
    // mesaj — nu prin excepție. Ramura asta e ușor de confundat cu succesul:
    // ambele redirectează la fel.
    $this->actingAs($this->user)
        ->from(route('dashboard'))
        ->post(route('report.store'), [
            'username' => $tinta->username,
            'subject' => 'avatar',
            'reason' => 'ofensator',
        ], $this->inertia)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error');
});

it('acceptă ciclul complet al unei nelămuriri', function () {
    $lesson = Lesson::where('status', 'published')->firstOrFail();
    $skill = Skill::findOrFail($lesson->skill_id);
    $blockId = $lesson->blocks[0]['id'];

    $this->actingAs($this->user)
        ->from(route('gaps.index'))
        ->post(route('gaps.store'), [
            'skill_id' => $skill->id,
            'lesson_id' => $lesson->id,
            'block_ids' => [$blockId],
            'note' => 'Nu înțeleg pasul al doilea.',
            'source' => 'lesson',
        ], $this->inertia)
        ->assertRedirect(route('gaps.index'));

    $gap = LearningGap::where('user_id', $this->user->id)->firstOrFail();

    $this->actingAs($this->user)
        ->from(route('gaps.index'))
        ->put(route('gaps.update', $gap), ['action' => 'resolve'], $this->inertia)
        ->assertRedirect(route('gaps.index'));

    $this->actingAs($this->user)
        ->from(route('gaps.index'))
        ->delete(route('gaps.destroy', $gap), [], $this->inertia)
        ->assertRedirect(route('gaps.index'));

    expect(LearningGap::find($gap->id))->toBeNull();
});

it('acceptă marcarea lecției ca parcursă', function () {
    $lesson = Lesson::where('status', 'published')->firstOrFail();
    $skill = Skill::findOrFail($lesson->skill_id);
    $subject = Subject::findOrFail($skill->subject_id);

    $url = route('course.show', [$subject->slug, $skill->slug]);

    $this->actingAs($this->user)
        ->from($url)
        ->post(route('course.complete', [$subject->slug, $skill->slug]), [], $this->inertia)
        ->assertRedirect($url);
});

it('parcurge antrenamentul: start, item, indiciu, răspuns', function () {
    $this->actingAs($this->user)
        ->post(route('practice.start'), ['subject' => $this->mate->slug], $this->inertia)
        ->assertRedirectContains('/antrenament/');

    $session = $this->user->sessions()->latest('id')->firstOrFail();

    ($this->inertiaGet)(route('practice.play', $session->uuid))
        ->assertOk()
        ->assertJsonPath('component', 'Practice/Play');

    $item = $session->sessionItems()->with('item')->firstOrFail()->item;

    $this->actingAs($this->user)
        ->from(route('practice.play', $session->uuid))
        ->post(route('practice.hint', $session->uuid), ['item_uuid' => $item->uuid], $this->inertia)
        ->assertRedirect(route('practice.play', $session->uuid));

    $key = $item->gradingKey();
    $answer = match (true) {
        isset($key['correct']) => ['choice' => $key['correct'][0]],
        isset($key['order']) => ['order' => $key['order']],
        isset($key['pairs']) => ['pairs' => $key['pairs']],
        isset($key['values']) => ['values' => array_map(fn ($v) => is_array($v) ? $v[0] : $v, $key['values'])],
        array_key_exists('value', $key) => ['value' => (string) $key['value']],
        default => [],
    };

    $this->actingAs($this->user)
        ->post(route('practice.answer', $session->uuid), [
            'client_uuid' => (string) Str::uuid(),
            'item_uuid' => $item->uuid,
            'session_uuid' => $session->uuid,
            'answer' => $answer,
        ], $this->inertia)
        ->assertOk()
        ->assertJsonPath('component', 'Practice/Play');
});

/*
|--------------------------------------------------------------------------
| Datele altcuiva
|--------------------------------------------------------------------------
*/

it('refuză accesul la sesiunea și la nelămuririle altui elev', function () {
    $altul = User::factory()->create();
    $altul->subjects()->attach($this->mate->id, ['is_primary' => true, 'sort_order' => 0, 'added_at' => now()]);

    $session = app(StartSession::class)(
        user: $altul,
        subjectId: $this->mate->id,
        mode: SessionMode::Practice,
        count: 2,
    );

    $this->actingAs($this->user)->get(route('practice.play', $session->uuid))->assertForbidden();

    $lesson = Lesson::where('status', 'published')->firstOrFail();
    $gap = LearningGap::create([
        'user_id' => $altul->id,
        'skill_id' => $lesson->skill_id,
        'lesson_id' => $lesson->id,
        'block_id' => $lesson->blocks[0]['id'],
        'excerpt' => 'x',
        'source' => 'lesson',
    ]);

    $this->actingAs($this->user)->put(route('gaps.update', $gap), ['action' => 'resolve'])->assertForbidden();
    $this->actingAs($this->user)->delete(route('gaps.destroy', $gap))->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| API v1 — tokenuri Sanctum, nu sesiuni
|--------------------------------------------------------------------------
*/

it('cere token pe API și răspunde JSON', function () {
    $this->getJson(route('api.v1.me'))->assertUnauthorized();
    $this->postJson(route('api.v1.attempts.store'), [])->assertUnauthorized();

    Sanctum::actingAs($this->user);

    $this->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJsonStructure(['id', 'username', 'xp', 'level', 'rating', 'streak_days']);

    $item = Item::practicePool()->where('subject_id', $this->mate->id)->firstOrFail();
    $key = $item->gradingKey();
    $answer = match (true) {
        isset($key['correct']) => ['choice' => $key['correct'][0]],
        isset($key['order']) => ['order' => $key['order']],
        isset($key['pairs']) => ['pairs' => $key['pairs']],
        isset($key['values']) => ['values' => array_map(fn ($v) => is_array($v) ? $v[0] : $v, $key['values'])],
        array_key_exists('value', $key) => ['value' => (string) $key['value']],
        default => [],
    };

    $this->postJson(route('api.v1.attempts.store'), [
        'client_uuid' => (string) Str::uuid(),
        'item_uuid' => $item->uuid,
        'answer' => $answer,
    ])->assertCreated();

    // Datele invalide dau 422, nu 500.
    $this->postJson(route('api.v1.attempts.store'), ['client_uuid' => 'nu-e-uuid'])
        ->assertStatus(422);
});

/**
 * `back()` fără referer cade pe `/` — adică pe pagina publică Blade.
 *
 * Aceeași capcană ca la deconectare: cererea a plecat prin XHR din aplicație,
 * iar XHR-ul ar primi o pagină fără anteturi Inertia și ar deschide modalul de
 * eroare. Browserul trimite de obicei `Referer`, deci nu se vede la clic — dar
 * dispare cu `Referrer-Policy: no-referrer`, într-o sesiune proaspătă sau la un
 * retry după expirare.
 *
 * Fiecare `back()` din zona aplicației are acum o rezervă din aplicație.
 */
it('nu cade în zona publică atunci când nu există referer', function () {
    $lesson = Lesson::where('status', 'published')->firstOrFail();
    $skill = Skill::findOrFail($lesson->skill_id);
    $subject = Subject::findOrFail($skill->subject_id);
    $tinta = User::factory()->create(['username' => 'tinta_fara_referer']);

    $scrieri = [
        ['post', route('report.store'), [
            'username' => $tinta->username, 'subject' => 'username', 'reason' => 'ofensator',
        ]],
        ['post', route('gaps.store'), ['skill_id' => $skill->id, 'lesson_id' => $lesson->id]],
        ['post', route('course.complete', [$subject->slug, $skill->slug]), []],
        ['put', route('settings.update'), [
            'username' => 'elev_fara_referer', 'timezone' => 'Europe/Chisinau', 'leaderboard_opt_in' => true,
        ]],
        ['put', route('settings.password'), [
            'current_password' => 'parola-sigura-123',
            'password' => 'inca-o-parola-789', 'password_confirmation' => 'inca-o-parola-789',
        ]],
        ['delete', route('settings.avatar.destroy'), []],
    ];

    foreach ($scrieri as [$method, $url, $payload]) {
        // Fără `from()`: nici referer, nici URL anterior în sesiune.
        $raspuns = $this->actingAs($this->user)->{$method}($url, $payload, $this->inertia);

        $destinatie = parse_url((string) $raspuns->headers->get('Location'), PHP_URL_PATH) ?: '/';
        $ruta = app('router')->getRoutes()->match(
            Request::create($destinatie)
        )->getName();

        expect($ruta)->not->toStartWith('public.', "{$method} {$url} a căzut în zona publică ({$destinatie})");
    }
});

it('lasă deconectarea accesibilă elevului obligat să-și schimbe numele', function () {
    // §10g: redenumirea forțată blochează navigarea, dar nu poate închide omul
    // înăuntru — setările și ieșirea rămân deschise.
    $this->user->forceFill(['username_reset_required' => true])->save();

    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertRedirect(route('settings.edit'));

    $this->actingAs($this->user)->get(route('settings.edit'))->assertOk();

    $this->actingAs($this->user)->post(route('logout'), [], $this->inertia)
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('public.home'));

    $this->assertGuest();
});

/*
|--------------------------------------------------------------------------
| Zona administrativă
|--------------------------------------------------------------------------
*/

it('ține elevul departe de tablourile administrative', function () {
    // Testele rulează în mediul `testing`, nu `local` — deci porțile se comportă
    // ca în producție. Verificate local, ar trece toate degeaba: Horizon și Pulse
    // se deschid singure în `local`.
    expect(app()->environment())->not->toBe('local');

    foreach (['/horizon', '/pulse'] as $url) {
        $this->get($url)->assertForbidden();
        $this->actingAs($this->user)->get($url)->assertForbidden();
    }

    $this->actingAs($this->user)->get('/admin')->assertForbidden();

    // Devtools-ul Inertia se înregistrează doar în `local`: în rest ruta nu
    // există deloc, nu doar că e interzisă.
    $this->get('/_inertia/devtools/entries')->assertNotFound();
});

it('lasă administratorul să ajungă la tablourile operaționale', function () {
    // Schela Laravel lăsa `viewHorizon` cu o listă goală de emailuri, iar Pulse
    // fără poartă: în producție nu intra NIMENI, nici administratorul. Coada e
    // infrastructură (§4) — dacă mastery-ul se blochează, cineva trebuie să vadă.
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/horizon')->assertOk();
    $this->actingAs($admin)->get('/pulse')->assertOk();
    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('nu dă editorului acces la coadă și la telemetrie', function () {
    // Editorul validează itemi, deci intră în Filament. Pulse arată interogări
    // lente cu parametri cu tot și cei mai activi utilizatori — date despre
    // minori (§9) care n-au de ce să treacă pe lângă rolul editorial.
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor)->get('/admin')->assertOk();
    $this->actingAs($editor)->get('/horizon')->assertForbidden();
    $this->actingAs($editor)->get('/pulse')->assertForbidden();
});
