<?php

use App\Domain\Grading\GraderRegistry;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Lesson;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed());

/**
 * Poartă automată de calitate pentru banca de itemi.
 *
 * Verifică structural TOT conținutul publicat, indiferent de disciplină. Prinde
 * exact clasa de greșeli care distruge încrederea elevilor și care e imposibil de
 * depistat prin recitire manuală când banca ajunge la mii de itemi: cheie de
 * corectare care nu există între variante, chei de pași care nu se potrivesc,
 * item fără competență.
 *
 * Rulează la fiecare commit. Un item generat care trece testele astea nu e
 * garantat corect pedagogic — dar e garantat coerent, iar validatorul uman se
 * poate concentra pe conținut, nu pe structură.
 */
it('fiecare item publicat are competență, sursă și explicație', function () {
    $probleme = [];

    foreach (Item::servable()->with('skills')->get() as $item) {
        $ref = $item->source_ref ?? "item#{$item->id}";

        if ($item->skills->isEmpty()) {
            $probleme[] = "$ref: fără competență asociată";
        }
        if (blank($item->source_ref)) {
            $probleme[] = "item#{$item->id}: fără source_ref (neverificabil)";
        }
        if (blank($item->explanation)) {
            $probleme[] = "$ref: fără explicație";
        }
        if ($item->hintCount() === 0) {
            $probleme[] = "$ref: fără indicii";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('cheia de corectare a itemilor cu variante există între variante', function () {
    $tipuri = [ItemType::Mcq, ItemType::NextStep, ItemType::FindError,
        ItemType::CodeOutput, ItemType::LabelDiagram];

    $probleme = [];

    foreach (Item::servable()->whereIn('type', $tipuri)->get() as $item) {
        $ref = $item->source_ref;
        $chei = collect($item->payload['choices'] ?? [])->pluck('key')->map('strval');
        $corecte = collect($item->gradingKey()['correct'] ?? [])->map('strval');

        if ($chei->isEmpty()) {
            $probleme[] = "$ref: fără variante în payload";

            continue;
        }
        if ($corecte->isEmpty()) {
            $probleme[] = "$ref: fără cheie de corectare";

            continue;
        }

        // Greșeala care ar face itemul imposibil de rezolvat corect.
        $lipsa = $corecte->diff($chei);
        if ($lipsa->isNotEmpty()) {
            $probleme[] = "$ref: cheia [{$lipsa->implode(', ')}] nu există între variante [{$chei->implode(', ')}]";
        }

        if ($chei->duplicates()->isNotEmpty()) {
            $probleme[] = "$ref: chei duplicate între variante";
        }
        if ($chei->count() < 2) {
            $probleme[] = "$ref: mai puțin de 2 variante";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('itemii de ordonare au chei care corespund pașilor din enunț', function () {
    $tipuri = [ItemType::OrderSteps, ItemType::Timeline, ItemType::BuildFormula];
    $probleme = [];

    foreach (Item::servable()->whereIn('type', $tipuri)->get() as $item) {
        $ref = $item->source_ref;
        $pasi = collect($item->payload['steps'] ?? [])->pluck('key')->map('strval')->sort()->values();
        $ordine = collect($item->gradingKey()['order'] ?? [])->map('strval')->sort()->values();

        if ($pasi->isEmpty() || $ordine->isEmpty()) {
            $probleme[] = "$ref: pași sau ordine lipsă";

            continue;
        }

        // Ordinea corectă trebuie să conțină exact pașii din enunț — altfel
        // itemul e nerezolvabil, iar punctajul parțial dă rezultate absurde.
        if ($pasi->all() !== $ordine->all()) {
            $probleme[] = "$ref: pașii [{$pasi->implode(',')}] ≠ ordinea [{$ordine->implode(',')}]";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('itemii de potrivire referă doar chei existente', function () {
    $probleme = [];

    foreach (Item::servable()->where('type', ItemType::Match)->get() as $item) {
        $ref = $item->source_ref;
        $stanga = collect($item->payload['left'] ?? [])->pluck('key')->map('strval');
        $dreapta = collect($item->payload['right'] ?? [])->pluck('key')->map('strval');
        $pairs = collect($item->gradingKey()['pairs'] ?? []);

        if ($pairs->isEmpty()) {
            $probleme[] = "$ref: fără perechi în cheie";

            continue;
        }
        if ($pairs->count() !== $stanga->count()) {
            $probleme[] = "$ref: {$pairs->count()} perechi pentru {$stanga->count()} elemente în stânga";
        }
        foreach ($pairs as $l => $r) {
            if (! $stanga->contains((string) $l)) {
                $probleme[] = "$ref: cheia stângă '$l' nu există";
            }
            if (! $dreapta->contains((string) $r)) {
                $probleme[] = "$ref: cheia dreaptă '$r' nu există";
            }
        }
        // Cu duplicate în dreapta, potrivirea devine ambiguă.
        if ($pairs->values()->duplicates()->isNotEmpty()) {
            $probleme[] = "$ref: aceeași opțiune din dreapta folosită de două ori (ambiguu)";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('itemii numerici au valoare de referință', function () {
    $probleme = [];

    foreach (Item::servable()->where('type', ItemType::Numeric)->get() as $item) {
        if (! array_key_exists('value', $item->gradingKey())) {
            $probleme[] = "{$item->source_ref}: fără 'value' în cheie";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('itemii de completare au un spațiu pentru fiecare valoare așteptată', function () {
    $tipuri = [ItemType::FillBlanks, ItemType::BalanceEquation];
    $probleme = [];

    foreach (Item::servable()->whereIn('type', $tipuri)->get() as $item) {
        $spatii = collect($item->payload['blanks'] ?? [])->pluck('key')->map('strval')->sort()->values();
        $valori = collect(array_keys($item->gradingKey()['values'] ?? []))->map('strval')->sort()->values();

        if ($spatii->all() !== $valori->all()) {
            $probleme[] = "{$item->source_ref}: spații [{$spatii->implode(',')}] ≠ valori [{$valori->implode(',')}]";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

/**
 * Specific informaticii: examenul acceptă Pascal sau C/C++, la alegerea
 * candidatului, iar itemii oficiali ANCE dau codul în ambele limbaje. Un item
 * cu cod într-un singur limbaj dezavantajează jumătate din candidați.
 */
it('itemii de informatică cu cod oferă și C++ și Pascal', function () {
    $probleme = [];

    $itemi = Item::servable()
        ->whereHas('subject', fn ($q) => $q->where('slug', 'informatica'))
        ->whereIn('type', [ItemType::CodeOutput, ItemType::FindError])
        ->get();

    expect($itemi)->not->toBeEmpty();

    foreach ($itemi as $item) {
        $variante = $item->payload['code_variants'] ?? null;

        if (! is_array($variante)) {
            $probleme[] = "{$item->source_ref}: fără code_variants";

            continue;
        }
        foreach (['cpp', 'pascal'] as $lang) {
            if (blank($variante[$lang] ?? null)) {
                $probleme[] = "{$item->source_ref}: lipsește varianta '$lang'";
            }
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

it('graful de prerechizite nu are cicluri', function () {
    $probleme = [];

    foreach (Skill::where('is_leaf', true)->get() as $skill) {
        $prereqs = collect($skill->transitivePrerequisites(6))->pluck('id');

        // O competență nu poate fi propriul prerechizit, nici indirect —
        // altfel diagnosticul ar intra în buclă.
        if ($prereqs->contains($skill->id)) {
            $probleme[] = "{$skill->slug}: ciclu în graful de prerechizite";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

/**
 * Fiecare item publicat trebuie să fie REZOLVABIL.
 *
 * Verificările de structură de mai sus spun că cheia e bine formată, nu că
 * graderul o acceptă. Un item cu toleranță zero pe o valoare zecimală, o cheie
 * de completare care nu se regăsește între opțiuni sau o potrivire scrisă
 * invers trec de ele și pică abia în fața elevului, care răspunde corect și
 * primește „greșit".
 *
 * Aici construim răspunsul corect DIN cheie și cerem punctaj maxim de la
 * graderul real.
 */
it('acordă punctaj maxim răspunsului construit din cheia de corectare', function () {
    $registry = app(GraderRegistry::class);
    $probleme = [];

    foreach (Item::servable()->get() as $item) {
        $cheie = $item->gradingKey();

        $raspuns = match ($item->type) {
            ItemType::Mcq, ItemType::NextStep, ItemType::FindError,
            ItemType::CodeOutput, ItemType::LabelDiagram => ['choice' => $cheie['correct'][0] ?? null],

            ItemType::OrderSteps, ItemType::Timeline,
            ItemType::BuildFormula => ['order' => $cheie['order'] ?? []],

            ItemType::Match => ['pairs' => $cheie['pairs'] ?? []],

            ItemType::Numeric => ['value' => (string) ($cheie['value'] ?? '')],

            // O valoare poate avea mai multe forme acceptate; prima e cea
            // canonică, iar elevul o alege dintre opțiunile afișate.
            ItemType::FillBlanks, ItemType::BalanceEquation => ['values' => array_map(
                fn ($v) => is_array($v) ? $v[0] : $v,
                $cheie['values'] ?? [],
            )],
        };

        $rezultat = $registry->for($item->type)->grade($item, $raspuns);

        if (! $rezultat->isCorrect || $rezultat->score < 1.0) {
            $probleme[] = "{$item->source_ref} ({$item->type->value}): scor {$rezultat->score}";
        }
    }

    expect($probleme)->toBeEmpty(
        "Itemi pe care propria lor cheie nu îi rezolvă:\n  ".implode("\n  ", $probleme)
    );
});

it('matricele și sistemele din LaTeX au trecerile la rând scrise corect', function () {
    // În PHP, '\\' între apostrofuri dă UN backslash. Un autor care scrie
    // '4 & 3 \\ 5 & 6' obține în bază `4 & 3 \ 5 & 6`: pentru KaTeX, `\ ` e
    // un spațiu, iar matricea se afișează pe un singur rând. KaTeX nu dă
    // eroare — elevul vede pur și simplu o problemă greșită. Corect: '\\\\'.
    $mediu = '/\\\\begin\{((?:[pbvBV])?matrix|cases|array|aligned)\}(.*?)\\\\end\{\1\}/s';
    $probleme = [];

    $verifica = function (string $ref, mixed $continut) use ($mediu, &$probleme) {
        foreach (Arr::flatten(Arr::wrap($continut)) as $sir) {
            if (! is_string($sir) || ! preg_match_all($mediu, $sir, $m)) {
                continue;
            }
            foreach ($m[2] as $i => $corp) {
                // Un `\` urmat de spațiu care NU face parte dintr-un `\\`.
                if (in_array($m[1][$i], ['matrix', 'pmatrix', 'bmatrix', 'vmatrix', 'Bmatrix', 'Vmatrix', 'cases'], true)
                    && preg_match('/(?<!\\\\)\\\\\s/', $corp)) {
                    $probleme[] = "{$ref}: {$m[1][$i]} cu `\\ ` în loc de `\\\\` — se afișează pe un singur rând";
                }
            }
        }
    };

    foreach (Item::all() as $item) {
        $verifica($item->source_ref, [$item->payload, $item->gradingKey(), $item->explanation, $item->hintList()]);
    }
    foreach (Lesson::all() as $lesson) {
        $verifica("lecția {$lesson->id}", $lesson->blocks);
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

/**
 * §11b: fiecare item se sprijină pe un document oficial, cu trimitere exactă.
 *
 * Deocamdată doar disciplinele refăcute pe surse oficiale. Pe măsură ce
 * celelalte sunt auditate, se adaugă aici — lista crește, nu scade.
 */
it('itemii disciplinelor auditate trimit la un document oficial ANCE', function () {
    $auditate = ['limba-romana', 'matematica'];
    $probleme = [];

    $itemi = Item::servable()
        ->whereHas('subject', fn ($q) => $q->whereIn('slug', $auditate))
        ->get();

    expect($itemi)->not->toBeEmpty();

    foreach ($itemi as $item) {
        if (! str_starts_with((string) $item->source, 'ance:')) {
            $probleme[] = "{$item->source_ref}: sursa „{$item->source}” nu e un document ANCE";
        }
        if (! preg_match('~^https://ance\.gov\.md/sites/default/files/[\w.-]+\.pdf#\S+$~', (string) $item->source_ref)) {
            $probleme[] = "{$item->source_ref}: source_ref nu duce la un PDF ANCE și la un item anume";
        }
    }

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});

/**
 * Testul ANCE cere să rezolvi sarcini; nu întreabă niciodată cum se notează.
 * Un item „câte puncte primește…" antrenează o întrebare care nu apare la
 * examen — informația despre barem stă în lecții, nu în Teste.
 */
it('nicio întrebare nu întreabă despre punctaj sau barem', function () {
    $probleme = Item::servable()->get()
        ->filter(fn (Item $item) => preg_match('~(câte puncte|\bbarem)~iu', (string) ($item->payload['stem'] ?? '')))
        ->map(fn (Item $item) => "{$item->source_ref}: {$item->payload['stem']}")
        ->values()
        ->all();

    expect($probleme)->toBeEmpty(implode("\n", $probleme));
});
