<?php

namespace App\Enums;

/**
 * Tipurile de item sunt independente de disciplină.
 *
 * Un tip nou = o valoare aici + un grader în App\Domain\Grading\Graders + un
 * component Vue în resources/js/components/items. Nicio migrație, nicio
 * modificare în motorul de joc.
 */
enum ItemType: string
{
    // Universale
    case Mcq = 'mcq';                     // alegere multiplă
    case Match = 'match';                 // potrivire (formulă ↔ definiție)
    case Numeric = 'numeric';             // răspuns numeric, cu toleranță
    case FillBlanks = 'fill_blanks';      // completează spațiile

    // Orientate pe baremul analitic de la BAC
    case NextStep = 'next_step';          // „care e pasul următor în rezolvare?"
    case OrderSteps = 'order_steps';      // ordonează pașii rezolvării
    case FindError = 'find_error';        // „unde s-a pierdut punctul?"

    // Specifice unor discipline
    case Timeline = 'timeline';           // istorie: ordonare cronologică
    case LabelDiagram = 'label_diagram';  // biologie: identifică structura
    case BalanceEquation = 'balance_equation'; // chimie: coeficienți
    case BuildFormula = 'build_formula';  // fizică: construiește formula din carduri
    case CodeOutput = 'code_output';      // informatică: ce afișează codul

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'Alegere multiplă',
            self::Match => 'Potrivire',
            self::Numeric => 'Răspuns numeric',
            self::FillBlanks => 'Completează spațiile',
            self::NextStep => 'Pasul următor',
            self::OrderSteps => 'Ordonează pașii',
            self::FindError => 'Găsește greșeala',
            self::Timeline => 'Cronologie',
            self::LabelDiagram => 'Identifică pe schemă',
            self::BalanceEquation => 'Echilibrează reacția',
            self::BuildFormula => 'Construiește formula',
            self::CodeOutput => 'Ce afișează codul',
        };
    }

    /**
     * Tipurile care se corectează pe pași acceptă punctaj parțial (0..1),
     * exact ca baremul analitic real de la BAC.
     */
    public function supportsPartialCredit(): bool
    {
        return in_array($this, [self::OrderSteps, self::Match, self::Timeline, self::FillBlanks, self::BalanceEquation], true);
    }

    /**
     * La itemii de recunoaștere (formule, definiții) viteza se premiază.
     * La cei de raționament NU — vrem ca elevul să gândească, nu să ghicească.
     */
    public function rewardsSpeed(): bool
    {
        return in_array($this, [self::Mcq, self::Match, self::Numeric], true);
    }
}
