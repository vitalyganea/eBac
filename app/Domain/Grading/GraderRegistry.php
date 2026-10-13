<?php

namespace App\Domain\Grading;

use App\Domain\Grading\Graders\FillBlanksGrader;
use App\Domain\Grading\Graders\MatchingGrader;
use App\Domain\Grading\Graders\NumericGrader;
use App\Domain\Grading\Graders\OrderingGrader;
use App\Domain\Grading\Graders\SingleChoiceGrader;
use App\Enums\ItemType;
use InvalidArgumentException;

/**
 * 12 tipuri de item, 5 graderi.
 *
 * Cele mai multe „mini-jocuri" diferă doar prin prezentare, nu prin corectare:
 * „găsește greșeala" și „ce afișează codul" sunt, mecanic, alegeri dintre
 * variante. Un tip nou de joc are de obicei ZERO cod de corectare nou.
 */
final class GraderRegistry
{
    /** @var array<string, class-string<Grader>> */
    private const MAP = [
        ItemType::Mcq->value => SingleChoiceGrader::class,
        ItemType::NextStep->value => SingleChoiceGrader::class,
        ItemType::FindError->value => SingleChoiceGrader::class,
        ItemType::CodeOutput->value => SingleChoiceGrader::class,
        ItemType::LabelDiagram->value => SingleChoiceGrader::class,

        ItemType::OrderSteps->value => OrderingGrader::class,
        ItemType::Timeline->value => OrderingGrader::class,
        ItemType::BuildFormula->value => OrderingGrader::class,

        ItemType::Match->value => MatchingGrader::class,
        ItemType::Numeric->value => NumericGrader::class,

        ItemType::FillBlanks->value => FillBlanksGrader::class,
        ItemType::BalanceEquation->value => FillBlanksGrader::class,
    ];

    public function for(ItemType $type): Grader
    {
        $class = self::MAP[$type->value] ?? throw new InvalidArgumentException(
            "Nu există grader pentru tipul de item [{$type->value}]."
        );

        return app($class);
    }

    /** @return list<ItemType> */
    public static function supportedTypes(): array
    {
        return array_map(fn (string $v) => ItemType::from($v), array_keys(self::MAP));
    }
}
