<?php

use App\Domain\Grading\Graders\FillBlanksGrader;
use App\Domain\Grading\Graders\MatchingGrader;
use App\Domain\Grading\Graders\NumericGrader;
use App\Domain\Grading\Graders\OrderingGrader;
use App\Domain\Grading\Graders\SingleChoiceGrader;
use App\Enums\ItemType;
use App\Models\Item;

/**
 * Corectarea și acordarea punctajului sunt singurul lucru din aplicație care
 * trebuie testat obsesiv: un bug aici distruge definitiv încrederea elevilor.
 * Testele sunt pure — nu ating baza de date.
 */
function item(ItemType $type, array $payload, array $answer): Item
{
    return new Item(['type' => $type, 'payload' => $payload, 'answer' => $answer]);
}

describe('SingleChoiceGrader', function () {
    it('acceptă varianta corectă', function () {
        $i = item(ItemType::Mcq, ['stem' => 'sin 30° = ?'], ['correct' => ['b']]);
        $r = (new SingleChoiceGrader)->grade($i, ['choice' => 'b']);
        expect($r->isCorrect)->toBeTrue()->and($r->score)->toBe(1.0);
    });

    it('respinge varianta greșită', function () {
        $i = item(ItemType::Mcq, ['stem' => 'sin 30° = ?'], ['correct' => ['b']]);
        expect((new SingleChoiceGrader)->grade($i, ['choice' => 'c'])->isCorrect)->toBeFalse();
    });

    it('penalizează bifele greșite la alegere multiplă', function () {
        $i = item(ItemType::Mcq, [], ['correct' => ['a', 'b'], 'multiple' => true]);
        // „bifez tot" nu trebuie să fie o strategie câștigătoare
        $r = (new SingleChoiceGrader)->grade($i, ['choices' => ['a', 'b', 'c', 'd']]);
        expect($r->score)->toBe(0.0);
    });

    it('dă punctaj parțial pentru o bifă corectă din două', function () {
        $i = item(ItemType::Mcq, [], ['correct' => ['a', 'b'], 'multiple' => true]);
        expect((new SingleChoiceGrader)->grade($i, ['choices' => ['a']])->score)->toBe(0.5);
    });

    it('nu acordă punctaj unui item fără cheie', function () {
        $i = item(ItemType::Mcq, [], []);
        expect((new SingleChoiceGrader)->grade($i, ['choice' => 'a'])->isCorrect)->toBeFalse();
    });
});

describe('OrderingGrader', function () {
    $key = ['order' => ['s1', 's2', 's3', 's4']];

    it('acceptă ordinea exactă', function () use ($key) {
        $i = item(ItemType::OrderSteps, [], $key);
        expect((new OrderingGrader)->grade($i, ['order' => ['s1', 's2', 's3', 's4']])->score)->toBe(1.0);
    });

    it('dă punctaj mare pentru o singură inversiune adiacentă', function () use ($key) {
        $i = item(ItemType::OrderSteps, [], $key);
        // 5 din 6 perechi rămân în ordinea relativă corectă
        $r = (new OrderingGrader)->grade($i, ['order' => ['s2', 's1', 's3', 's4']]);
        expect($r->score)->toBeGreaterThan(0.8)->and($r->isCorrect)->toBeFalse();
    });

    it('dă zero pentru ordinea complet inversată', function () use ($key) {
        $i = item(ItemType::OrderSteps, [], $key);
        expect((new OrderingGrader)->grade($i, ['order' => ['s4', 's3', 's2', 's1']])->score)->toBe(0.0);
    });

    it('respinge o ordonare incompletă', function () use ($key) {
        $i = item(ItemType::OrderSteps, [], $key);
        expect((new OrderingGrader)->grade($i, ['order' => ['s1', 's2']])->isCorrect)->toBeFalse();
    });
});

describe('MatchingGrader', function () {
    it('dă punctaj parțial pe perechi corecte', function () {
        $i = item(ItemType::Match, [], ['pairs' => ['l1' => 'r1', 'l2' => 'r2', 'l3' => 'r3', 'l4' => 'r4']]);
        $r = (new MatchingGrader)->grade($i, ['pairs' => ['l1' => 'r1', 'l2' => 'r2', 'l3' => 'r9', 'l4' => 'r8']]);
        expect($r->score)->toBe(0.5);
    });
});

describe('NumericGrader', function () {
    it('acceptă în limita toleranței', function () {
        $i = item(ItemType::Numeric, [], ['value' => 3.14159, 'tolerance' => 0.01]);
        expect((new NumericGrader)->grade($i, ['value' => 3.14])->isCorrect)->toBeTrue();
    });

    it('respinge peste toleranță', function () {
        $i = item(ItemType::Numeric, [], ['value' => 3.14159, 'tolerance' => 0.01]);
        expect((new NumericGrader)->grade($i, ['value' => 3.1])->isCorrect)->toBeFalse();
    });

    it('acceptă virgula ca separator zecimal', function () {
        // Elevii din Moldova scriu 3,14 — nu 3.14
        $i = item(ItemType::Numeric, [], ['value' => 3.14, 'tolerance' => 0.001]);
        expect((new NumericGrader)->grade($i, ['value' => '3,14'])->isCorrect)->toBeTrue();
    });

    it('respinge intrarea nenumerică', function () {
        $i = item(ItemType::Numeric, [], ['value' => 5]);
        expect((new NumericGrader)->grade($i, ['value' => 'nu stiu'])->isCorrect)->toBeFalse();
    });
});

describe('FillBlanksGrader', function () {
    it('acceptă variante echivalente', function () {
        $i = item(ItemType::FillBlanks, ['stem_template' => 'S = ___ · h'], [
            'values' => ['b1' => ['a/2', '0.5a', 'a:2']],
        ]);
        expect((new FillBlanksGrader)->grade($i, ['values' => ['b1' => '0.5a']])->isCorrect)->toBeTrue();
    });

    it('ignoră diferența de majuscule implicit', function () {
        $i = item(ItemType::BalanceEquation, [], ['values' => ['b1' => 'NaCl']]);
        expect((new FillBlanksGrader)->grade($i, ['values' => ['b1' => 'nacl']])->isCorrect)->toBeTrue();
    });

    it('dă punctaj parțial pe spații', function () {
        $i = item(ItemType::BalanceEquation, [], ['values' => ['a' => '2', 'b' => '1', 'c' => '2']]);
        $r = (new FillBlanksGrader)->grade($i, ['values' => ['a' => '2', 'b' => '1', 'c' => '9']]);
        expect(round($r->score, 3))->toBe(0.667);
    });
});
