<?php

use App\Domain\Essays\InvalidSelfAssessment;
use App\Domain\Essays\ScoreEssay;
use Database\Seeders\Eseu\Rubrici;

/**
 * Punctajul autoevaluării, pe benzile din baremele ANCE de limba engleză.
 * Pur: nu atinge baza de date.
 */
function rubricFor(array $language, int $total, array $criteria = []): array
{
    return ['total' => $total, 'criteria' => $criteria, ...$language];
}

it('numără cuvintele ca baremul: orice semn separat prin spații', function () {
    // „-" și „." separate prin spații se numără și ele: 8 „cuvinte".
    expect(ScoreEssay::countWords("I think - AI is   useful .\n\nYes"))->toBe(8)
        ->and(ScoreEssay::countWords('   '))->toBe(0);
});

it('scade un punct la fiecare N greșeli, din plafonul dat de volum (2026)', function () {
    $r = rubricFor(Rubrici::enEseu26(), 16);
    $score = new ScoreEssay;

    // 190 de cuvinte: plafon 12, volum 4; 7 greșeli → −1.
    expect($score($r, [], ['limba' => 7], 190)['score'])->toBe(11 + 4);

    // 145 de cuvinte: plafon 8 (banda 140–149), volum 2 (110–149).
    expect($score($r, [], ['limba' => 0], 145)['score'])->toBe(8 + 2);

    // Peste 60 de greșeli nu mai scade sub zero.
    expect($score($r, [], ['limba' => 90], 190)['score'])->toBe(0 + 4);
});

it('depășirea limitei: 0 la volum în baremele vechi, maximul în cel din 2026', function () {
    $score = new ScoreEssay;

    $vechi = rubricFor(Rubrici::enArticol2425(), 12);
    $nou = rubricFor(Rubrici::enArticol26(), 12);

    $volum = fn (array $r, int $w) => collect($score($r, [], ['lexic' => 0, 'gramatica' => 0, 'limba' => 0], $w)['breakdown'])
        ->firstWhere('key', 'volum')['points'];

    expect($volum($vechi, 120))->toBe(0)    // „puncte nu se acordă"
        ->and($volum($nou, 130))->toBe(3)    // „puncte nu se adaugă"
        ->and($volum($vechi, 95))->toBe(3)
        ->and($volum($vechi, 60))->toBe(0);
});

it('fără tabel de plafon (2022–2023), plafonul e punctajul maxim', function () {
    $r = rubricFor(Rubrici::enEseu2223(), 16);
    $res = (new ScoreEssay)($r, [], ['lexic' => 3, 'gramatica' => 8], 130);

    $by = collect($res['breakdown'])->keyBy('key');
    expect($by['lexic']['points'])->toBe(5)      // −1 la 3 greșeli
        ->and($by['gramatica']['points'])->toBe(4) // −2 la 8 greșeli, câte 4
        ->and($by['volum']['points'])->toBe(1);  // 120–139
});

it('respinge un punctaj care nu există în barem', function () {
    $r = rubricFor([], 4, [['key' => 'a', 'group' => 'g', 'label' => 'Două domenii', 'options' => [[0, ''], [2, ''], [4, '']]]]);

    expect(fn () => (new ScoreEssay)($r, ['a' => 3], [], null))->toThrow(InvalidSelfAssessment::class)
        ->and((new ScoreEssay)($r, ['a' => 4], [], null)['score'])->toBe(4);
});

it('cere numărul de cuvinte unde baremul punctează volumul', function () {
    $r = rubricFor(Rubrici::enArticol26(), 12);

    expect(fn () => (new ScoreEssay)($r, [], ['limba' => 0], null))->toThrow(InvalidSelfAssessment::class);
});
