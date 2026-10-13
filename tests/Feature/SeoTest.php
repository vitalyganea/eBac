<?php

use App\Models\Item;
use App\Models\Skill;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed());

it('paginile publice se randează fără autentificare', function () {
    $subject = Subject::where('slug', 'matematica')->firstOrFail();
    $skill = Skill::where('subject_id', $subject->id)
        ->whereHas('items', fn ($q) => $q->practicePool())->firstOrFail();

    $this->get(route('public.home'))->assertOk();
    $this->get(route('public.subject', $subject->slug))->assertOk();
    $this->get(route('public.skill', [$subject->slug, $skill->slug]))->assertOk();
    $this->get(route('public.exam', '2027'))->assertOk();
    $this->get(route('public.sitemap'))->assertOk();
});

it('conținutul e prezent în HTML, fără JavaScript', function () {
    // Testăm ieșirea de PRODUCȚIE. În dezvoltare, Vite își injectează legitim
    // clientul de hot-reload (`public/hot` există cât rulează `composer dev`),
    // ceea ce ar face aserțiunea de mai jos să pice fără niciun motiv real.
    Vite::useHotFile(base_path('tests/.no-hot'));

    $subject = Subject::where('slug', 'matematica')->firstOrFail();
    $skill = Skill::where('subject_id', $subject->id)
        ->whereHas('items', fn ($q) => $q->practicePool())->firstOrFail();

    $html = $this->get(route('public.skill', [$subject->slug, $skill->slug]))->getContent();

    // Textul trebuie să existe în sursă, nu să fie randat de client.
    expect($html)->toContain($skill->name)
        ->and($html)->toContain($subject->name);

    // Nicio intrare JS pe paginile publice: crawlerele nu trebuie să randeze,
    // iar telefoanele ieftine nu trebuie să plătească pentru un bundle inutil.
    expect($html)->not->toMatch('/<script[^>]*\ssrc=/')
        ->and($html)->not->toContain('resources/js/app.ts')
        // Dar CSS-ul trebuie să fie acolo.
        ->and($html)->toMatch('/<link[^>]+rel="stylesheet"/');
});

it('are metadate complete și canonical', function () {
    $subject = Subject::where('slug', 'informatica')->firstOrFail();
    $html = $this->get(route('public.subject', $subject->slug))->getContent();

    expect($html)->toContain('<title>')
        ->and($html)->toMatch('/<meta name="description" content="[^"]{50,}"/')
        ->and($html)->toContain('rel="canonical"')
        ->and($html)->toContain('hreflang="ro-MD"')
        ->and($html)->toContain('property="og:title"')
        // Țintire geografică explicită pentru Republica Moldova.
        ->and($html)->toContain('name="geo.region" content="MD"')
        ->and($html)->toContain('index, follow');
});

it('emite date structurate valide', function () {
    $subject = Subject::where('slug', 'matematica')->firstOrFail();
    $skill = Skill::where('subject_id', $subject->id)
        ->whereHas('items', fn ($q) => $q->practicePool()->where('type', 'mcq'))
        ->firstOrFail();

    $html = $this->get(route('public.skill', [$subject->slug, $skill->slug]))->getContent();

    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
    $data = json_decode($m[1] ?? '', true);

    expect($data)->toBeArray()
        ->and($data['@context'])->toBe('https://schema.org');

    $types = collect($data['@graph'])->pluck('@type');

    expect($types)->toContain('EducationalOrganization', 'BreadcrumbList', 'Quiz');

    $org = collect($data['@graph'])->firstWhere('@type', 'EducationalOrganization');
    expect($org['areaServed']['identifier'])->toBe('MD')
        ->and($org['inLanguage'])->toBe('ro-MD');
});

/**
 * INVARIANTUL CRITIC.
 *
 * Publicarea unui item pe web îl arde: oricine îl găsește cu răspuns cu tot.
 * Itemii de holdout sunt rezervați simulărilor — dacă ajung public, estimarea
 * de notă nu mai măsoară nimic.
 */
it('nu publică NICIODATĂ itemi de holdout', function () {
    $holdout = Item::examPool()->with('skills.subject')->get();

    expect($holdout)->not->toBeEmpty();

    foreach ($holdout as $item) {
        $skill = $item->skills->first();
        $stem = data_get($item->payload, 'stem');

        if ($skill === null || blank($stem)) {
            continue;
        }

        $html = $this->get(route('public.skill', [$skill->subject->slug, $skill->slug]))->getContent();

        expect($html)->not->toContain($stem, "Item de holdout expus public: {$item->source_ref}");

        // Nici explicația, nici indiciile lui.
        if ($item->explanation) {
            expect($html)->not->toContain($item->explanation);
        }
    }
});

it('paginile aplicației sunt marcate noindex', function () {
    $user = User::factory()->create();
    $user->subjects()->attach(Subject::where('slug', 'matematica')->value('id'), [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    expect($html)->toContain('noindex');
});

/**
 * Regresie: directiva @routes serializa TOATĂ harta aplicației în fiecare
 * pagină — inclusiv /admin, /horizon, /pulse și ruta PUT de upload în storage.
 */
it('nu expune rutele administrative în HTML', function () {
    $user = User::factory()->create();
    $user->subjects()->attach(Subject::where('slug', 'matematica')->value('id'), [
        'is_primary' => true, 'sort_order' => 0, 'added_at' => now(),
    ]);

    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

    foreach (['admin/items', 'horizon/api', 'pulse', 'livewire', 'storage/{path}'] as $secret) {
        expect($html)->not->toContain($secret);
    }
});

it('sitemap-ul listează doar pagini cu conținut', function () {
    $xml = $this->get(route('public.sitemap'))->getContent();

    expect($xml)->toContain('<urlset')
        ->and($xml)->toContain(route('public.home'));

    // O competență fără exerciții publice ar fi conținut subțire trimis la
    // indexare — trage în jos tot domeniul.
    $goale = Skill::where('is_active', true)
        ->whereDoesntHave('items', fn ($q) => $q->practicePool())
        ->with('subject')->get();

    foreach ($goale as $skill) {
        expect($xml)->not->toContain(route('public.skill', [$skill->subject->slug, $skill->slug]));
    }

    // Și nicio pagină a aplicației.
    foreach (['/panou', '/antrenament', '/admin'] as $privat) {
        expect($xml)->not->toContain(url($privat));
    }
});

it('întoarce 404 pentru discipline și competențe inexistente', function () {
    $this->get('/disciplina-inexistenta')->assertNotFound();
    $this->get('/matematica/tema-inexistenta')->assertNotFound();
});

it('rutele publice nu înghit rutele aplicației', function () {
    // /{subject} e înregistrată ultima, dar merită blocat cu test.
    $this->get('/intra')->assertOk();
    $this->get('/inregistrare')->assertOk();
    $this->get('/panou')->assertRedirect(route('login'));
});
