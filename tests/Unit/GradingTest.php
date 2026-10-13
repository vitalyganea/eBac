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

    it('acceptă oricare dintre variantele admise de barem', function () {
        // ANCE 2024: „mărire" → baremul acceptă faimă, cinste, elogiu, laudă, rang.
        $i = item(ItemType::Mcq, [], ['correct' => ['c', 'd'], 'any' => true]);
        $g = new SingleChoiceGrader;

        expect($g->grade($i, ['choice' => 'c'])->isCorrect)->toBeTrue()
            ->and($g->grade($i, ['choice' => 'd'])->isCorrect)->toBeTrue()
            ->and($g->grade($i, ['choice' => 'a'])->isCorrect)->toBeFalse()
            // Două bife nu sunt „o variantă din listă".
            ->and($g->grade($i, ['choices' => ['c', 'd']])->isCorrect)->toBeFalse();
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
    it('acceptă indicele bazei scris de elev la conversii', function (string $raspuns, bool $corect) {
        // Enunțul și explicația scriu 13_{(10)}, deci elevul poate tasta și
        // indicele. Valoarea e corectă — a fost doar scrisă complet.
        $i = item(ItemType::Numeric, ['unit' => 'în baza 10'], ['value' => 13, 'tolerance' => 0, 'base' => 10]);

        expect((new NumericGrader)->grade($i, ['value' => $raspuns])->isCorrect)->toBe($corect);
    })->with([
        ['13', true], ['13(10)', true], ['13 (10)', true], ['13_10', true],
        ['13_{10}', true], ['13₁₀', true], ['13₍₁₀₎', true],
        // Fără separator e alt număr, iar altă bază e alt răspuns.
        ['1310', false], ['13(2)', false], ['1101', false],
    ]);

    it('acceptă formele firești ale unui răspuns corect', function (array $payload, array $key, string $raspuns, bool $corect) {
        $i = item(ItemType::Numeric, $payload, $key);

        expect((new NumericGrader)->grade($i, ['value' => $raspuns])->isCorrect)->toBe($corect);
    })->with([
        'fracție' => [[], ['value' => 1.5, 'tolerance' => 0.001], '3/2', true],
        'fracție cu spații' => [[], ['value' => 0.75, 'tolerance' => 0.001], '3 / 4', true],
        'fracție greșită' => [[], ['value' => 1.5, 'tolerance' => 0.001], '2/3', false],
        'împărțire la zero' => [[], ['value' => 1.5], '3/0', false],
        'multiplu de π' => [['unit' => 'π'], ['value' => 15], '15π', true],
        'pi scris cu litere' => [['unit' => 'π'], ['value' => 15], '15 pi', true],
        'grade' => [['unit' => '°'], ['value' => 90], '90°', true],
        'grade în cuvinte' => [['unit' => '°'], ['value' => 90], '90 grade', true],
        'procent' => [['unit' => '%'], ['value' => 2, 'tolerance' => 0.01], '2%', true],
        'biți' => [['unit' => 'biți'], ['value' => 5], '5 biti', true],
        'atribuire' => [[], ['value' => 3], 'x = 3', true],
        'atribuire greșită' => [[], ['value' => 3], 'x = 4', false],
        'spațiu de mii' => [[], ['value' => 8192], '8 192', true],
        'minus tipografic' => [[], ['value' => -5], '−5', true],
        // Unitatea se acceptă doar dacă e a itemului.
        'procent la item fără unitate' => [[], ['value' => 0.5, 'tolerance' => 0.01], '50%', false],
        'π la item fără unitate' => [[], ['value' => 15], '15π', false],
        'text oarecare' => [[], ['value' => 15], 'cincisprezece', false],
        // Forma exactă din baremul ANCE.
        'radical și π' => [[], ['value' => 9 * sqrt(2) * M_PI, 'tolerance' => 0.01], '9√2π', true],
        'radical scris sqrt' => [[], ['value' => 9 * sqrt(2) * M_PI, 'tolerance' => 0.01], '9sqrt2 pi', true],
        'fracție cu radical' => [[], ['value' => 500 * sqrt(3) / 3, 'tolerance' => 0.01], '500√3/3', true],
        'sumă în paranteză' => [[], ['value' => 16 + 4 * sqrt(3), 'tolerance' => 0.01], '(16 + 4√3)', true],
        'aproximare zecimală' => [[], ['value' => 16 + 4 * sqrt(3), 'tolerance' => 0.01], '22,93', true],
        'constanta e' => [[], ['value' => 1 - 2 / M_E, 'tolerance' => 0.005], '1 - 2/e', true],
        'radical greșit' => [[], ['value' => 500 * sqrt(3) / 3, 'tolerance' => 0.01], '500√2/3', false],
        'radical din negativ' => [[], ['value' => 2], '√-4', false],
        'cod, nu număr' => [[], ['value' => 2], 'system(1)', false],
    ]);

    it('nu îndepărtează nimic la itemii fără bază declarată', function () {
        $i = item(ItemType::Numeric, [], ['value' => 13, 'tolerance' => 0]);
        expect((new NumericGrader)->grade($i, ['value' => '13(10)'])->isCorrect)->toBeFalse();
    });

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

    it('compară spațiile numerice ca valori', function () {
        // BAC 2026, profil real, itemul 4: p = 2, q = −14.
        $i = item(ItemType::FillBlanks, [], ['values' => ['p' => '2', 'q' => '-14'], 'numeric' => true]);
        $g = new FillBlanksGrader;

        expect($g->grade($i, ['values' => ['p' => '2', 'q' => '−14']])->isCorrect)->toBeTrue()
            ->and($g->grade($i, ['values' => ['p' => 'p = 2', 'q' => '-28/2']])->isCorrect)->toBeTrue()
            ->and($g->grade($i, ['values' => ['p' => '2', 'q' => '14']])->score)->toBe(0.5);
    });

    it('dă punctaj parțial pe spații', function () {
        $i = item(ItemType::BalanceEquation, [], ['values' => ['a' => '2', 'b' => '1', 'c' => '2']]);
        $r = (new FillBlanksGrader)->grade($i, ['values' => ['a' => '2', 'b' => '1', 'c' => '9']]);
        expect(round($r->score, 3))->toBe(0.667);
    });
});
