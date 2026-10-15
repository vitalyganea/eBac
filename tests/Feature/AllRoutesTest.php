<?php

use App\Domain\Sessions\StartSession;
use App\Enums\SessionMode;
use App\Models\Formula;
use App\Models\Item;
use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * TOATE rutele înregistrate — ale noastre, Filament, Livewire, Horizon, Pulse,
 * Sanctum — apelate cu fiecare metodă, de fiecare tip de utilizator, cu
 * parametri reali.
 *
 * Niciuna nu are voie să răspundă cu 5xx. Testul nu are o listă de rute: le ia
 * din router, deci o rută adăugată mâine e verificată automat.
 *
 * L-am scris după ce /admin/aprobari a ajuns să cadă cu 500 fără ca vreun test
 * să observe: RouteCoverageTest verifica doar /admin. La primul audit complet
 * au ieșit la iveală și rutele de export ale Filament (tabele lipsă, 500 chiar
 * și pentru vizitatori) și resursele Livewire cu nume inventat.
 *
 * Fiecare cerere rulează într-un savepoint: o eroare SQL nu otrăvește
 * tranzacția testului, iar o rută care șterge ceva nu le strică pe următoarele.
 */
beforeEach(function () {
    $this->seed();

    $mk = function (string $role) {
        $user = User::factory()->create(['role' => $role]);
        foreach (Subject::all() as $i => $subject) {
            $user->subjects()->attach($subject->id, ['is_primary' => $i === 0, 'sort_order' => $i, 'added_at' => now()]);
        }

        return $user;
    };

    $this->actors = ['vizitator' => null, 'elev' => $mk('student'), 'editor' => $mk('editor'), 'admin' => $mk('admin')];

    $this->mate = Subject::where('slug', 'matematica')->firstOrFail();
    $this->lesson = Lesson::published()->with('skill')
        ->whereHas('skill', fn ($q) => $q->where('subject_id', $this->mate->id))
        ->firstOrFail();

    $this->fixtures = [];
    foreach ($this->actors as $name => $user) {
        $this->fixtures[$name] = [
            'session' => $user ? app(StartSession::class)($user, $this->mate->id, SessionMode::Practice, 3)->uuid : (string) Str::uuid(),
            'gap' => $user
                ? LearningGap::create(['user_id' => $user->id, 'skill_id' => $this->lesson->skill_id, 'source' => 'lesson'])->id
                : 1,
            'formula' => Formula::firstOrFail()->id,
        ];
    }
});

function urlFor(RouteDefinition $route, array $params): string
{
    $uri = preg_replace_callback(
        '/\{(\w+)(?::\w+)?(\?)?\}/',
        fn ($m) => (string) ($params[$m[1]] ?? (($m[2] ?? '') === '?' ? '' : 'x')),
        $route->uri(),
    );

    return '/'.trim(preg_replace('#/+#', '/', $uri), '/');
}

it('nicio rută nu răspunde cu 5xx, pentru niciun tip de utilizator', function () {
    $erori = [];
    $verificate = 0;

    foreach (Route::getRoutes() as $route) {
        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            foreach ($this->actors as $name => $user) {
                $url = urlFor($route, [
                    ...$this->fixtures[$name],
                    'slug' => $this->mate->slug,
                    'subject' => $this->mate->slug,
                    'skill' => $this->lesson->skill->slug,
                    'year' => '2027',
                    'record' => Item::first()->id,
                ]);

                // Un apel pe ruta API autentificată schimbă garda implicită pe
                // `sanctum` pentru tot restul procesului. În producție fiecare
                // cerere pornește curat; aici o resetăm explicit.
                $this->app['auth']->forgetGuards();
                $this->app['auth']->shouldUse('web');

                DB::beginTransaction();
                try {
                    $response = ($user ? $this->actingAs($user) : $this)
                        ->call($method, $url, [], [], [], ['HTTP_ACCEPT' => 'text/html']);

                    if ($response->getStatusCode() >= 500) {
                        $mesaj = $response->exception?->getMessage() ?? '';
                        $erori[] = "{$method} {$url} [{$name}] → {$response->getStatusCode()} ".mb_substr($mesaj, 0, 200);
                    }
                } finally {
                    DB::rollBack();
                }

                $verificate++;
            }
        }
    }

    // A doua trecere: parametri INVALIZI. Un URL modificat de mână („/x/",
    // un id inexistent) trebuie să dea 404, nu o excepție din Postgres, care
    // e strict cu tipurile: `where id = 'x'` pe o coloană bigint e eroare.
    foreach (['x', '999999', '00000000-0000-0000-0000-000000000000'] as $invalid) {
        foreach (Route::getRoutes() as $route) {
            if (! str_contains($route->uri(), '{')) {
                continue;
            }
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->app['auth']->forgetGuards();
                $this->app['auth']->shouldUse('web');

                $url = urlFor($route, array_fill_keys(
                    ['session', 'gap', 'formula', 'slug', 'subject', 'skill', 'year', 'record', 'export', 'import', 'id', 'tag', 'path', 'component', 'filename'],
                    $invalid,
                ));

                DB::beginTransaction();
                try {
                    $response = $this->actingAs($this->actors['admin'])
                        ->call($method, $url, [], [], [], ['HTTP_ACCEPT' => 'text/html']);

                    if ($response->getStatusCode() >= 500) {
                        $mesaj = $response->exception?->getMessage() ?? '';
                        $erori[] = "{$method} {$url} [parametru invalid] → {$response->getStatusCode()} ".mb_substr($mesaj, 0, 200);
                    }
                } finally {
                    DB::rollBack();
                }

                $verificate++;
            }
        }
    }

    expect($verificate)->toBeGreaterThan(300)
        ->and($erori)->toBeEmpty("Rute cu erori de server:\n".implode("\n", $erori));
});

it('cere autentificare pe fiecare rută a aplicației și a panoului', function () {
    $probleme = [];

    foreach (Route::getRoutes() as $route) {
        $middleware = implode(' ', $route->gatherMiddleware());
        $method = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');

        // Rutele noastre cu `auth` și paginile Filament (care au propriul
        // middleware de autentificare).
        $isApp = str_contains($middleware, 'auth') && ! str_starts_with($route->uri(), 'api/');
        if (! $isApp) {
            continue;
        }

        $url = urlFor($route, [...$this->fixtures['vizitator'], 'slug' => 'matematica', 'subject' => 'matematica', 'skill' => 'x', 'record' => 1]);
        $status = $this->call($method, $url)->getStatusCode();

        if (! in_array($status, [302, 401, 403], true)) {
            $probleme[] = "{$method} {$url} → {$status} pentru vizitator";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('ține elevul departe de panou, coadă și telemetrie', function () {
    $student = $this->actors['elev'];

    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        if (! preg_match('#^(admin(?!/login)|horizon|pulse)#', $uri) || ! in_array('GET', $route->methods(), true)) {
            continue;
        }

        $url = urlFor($route, ['record' => Item::first()->id, 'view' => null, 'id' => 1, 'tag' => 'x']);

        expect($this->actingAs($student)->get($url)->getStatusCode())
            ->toBe(403, "GET {$url} trebuie să fie interzis elevului");
    }
});

it('dă fiecărei limitări de rată propria cheie', function () {
    // `throttle:10,1` fără prefix numără pe aceeași cheie (utilizatorul sau
    // IP-ul) TOATE rutele limitate. Zece răspunsuri la antrenament blocau
    // pornirea simulării cu 429.
    $fara = [];

    foreach (Route::getRoutes() as $route) {
        // Ruta de upload a Livewire vine din pachet. După ce toate rutele
        // noastre au prefix, ea e singura pe cheia implicită.
        if (str_starts_with($route->uri(), 'livewire')) {
            continue;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && preg_match('/^throttle:\d+,\d+$/', $middleware)) {
                $fara[] = implode('|', $route->methods()).' '.$route->uri()." ({$middleware})";
            }
        }
    }

    expect($fara)->toBeEmpty("Limitări fără prefix:\n".implode("\n", $fara));
});
