<?php

namespace App\Domain\Grading;

/**
 * Valoarea unei expresii numerice scrise de elev: „9√2π", „500√3/3",
 * „(16 + 4√3)", „2^(-1)".
 *
 * Baremele ANCE dau răspunsul exact, cu radicali și π. Un elev care scrie
 * „9√2π" a răspuns exact ca baremul, deci nu-l putem obliga să tasteze 39,98.
 *
 * Gramatica e deliberat mică — numere, + − · /, ^, paranteze, √, π, e și
 * înmulțirea implicită („2√3", „9π"). Nu există variabile, funcții sau `eval`:
 * intrarea vine din browser.
 */
final class NumberExpression
{
    /** O expresie de răspuns nu are de ce să fie mai lungă; plafonul oprește abuzul. */
    private const MAX_LENGTH = 60;

    private const MAX_DEPTH = 8;

    /** @var list<string> */
    private array $tokens = [];

    private int $pos = 0;

    private int $depth = 0;

    public static function evaluate(string $input): ?float
    {
        $input = trim($input);

        if ($input === '' || mb_strlen($input) > self::MAX_LENGTH) {
            return null;
        }

        $parser = new self;
        $tokens = $parser->tokenize($input);

        if ($tokens === null) {
            return null;
        }

        $parser->tokens = $tokens;

        try {
            $value = $parser->expression();
        } catch (\UnexpectedValueException) {
            return null;
        }

        if ($parser->pos !== count($tokens) || ! is_finite($value)) {
            return null;
        }

        return $value;
    }

    /** @return list<string>|null */
    private function tokenize(string $s): ?array
    {
        $s = strtr($s, [
            'sqrt' => '√', 'rad' => '√', 'pi' => 'π', 'Pi' => 'π', 'PI' => 'π',
            '·' => '*', '×' => '*', '⋅' => '*', ':' => '/', '÷' => '/',
            '−' => '-', '–' => '-', '[' => '(', ']' => ')',
        ]);

        preg_match_all('/\d+(?:\.\d+)?|[+\-*\/^()√πe]|\s+|./u', $s, $m);

        $tokens = [];
        foreach ($m[0] as $t) {
            if (trim($t) === '') {
                continue;
            }
            if (! preg_match('/^(\d+(?:\.\d+)?|[+\-*\/^()√πe])$/u', $t)) {
                return null;
            }
            $tokens[] = $t;
        }

        return $tokens === [] ? null : $tokens;
    }

    private function peek(): ?string
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function take(): string
    {
        $t = $this->tokens[$this->pos++] ?? null;

        if ($t === null) {
            throw new \UnexpectedValueException;
        }

        return $t;
    }

    private function expression(): float
    {
        $value = $this->term();

        while (in_array($this->peek(), ['+', '-'], true)) {
            $value = $this->take() === '+' ? $value + $this->term() : $value - $this->term();
        }

        return $value;
    }

    private function term(): float
    {
        $value = $this->unary();

        while (true) {
            $next = $this->peek();

            if ($next === '*') {
                $this->take();
                $value *= $this->unary();
            } elseif ($next === '/') {
                $this->take();
                $divisor = $this->unary();
                if ($divisor == 0.0) {
                    throw new \UnexpectedValueException;
                }
                $value /= $divisor;
            } elseif ($next !== null && $this->startsAtom($next)) {
                // Înmulțire implicită: „2√3", „9π", „2(1+√5)".
                $value *= $this->power();
            } else {
                return $value;
            }
        }
    }

    private function unary(): float
    {
        if ($this->peek() === '-') {
            $this->take();

            return -$this->unary();
        }
        if ($this->peek() === '+') {
            $this->take();

            return $this->unary();
        }

        return $this->power();
    }

    private function power(): float
    {
        $base = $this->atom();

        if ($this->peek() === '^') {
            $this->take();

            return (float) ($base ** $this->unary());
        }

        return $base;
    }

    private function atom(): float
    {
        $t = $this->take();

        if ($t === '(') {
            if (++$this->depth > self::MAX_DEPTH) {
                throw new \UnexpectedValueException;
            }
            $value = $this->expression();
            $this->depth--;
            if ($this->take() !== ')') {
                throw new \UnexpectedValueException;
            }

            return $value;
        }

        if ($t === 'π') {
            return M_PI;
        }

        // Ariile din analiză au răspunsuri ca „1 − 2/e".
        if ($t === 'e') {
            return M_E;
        }

        if ($t === '√') {
            // √ se leagă de atomul imediat următor: „√2π" = √2 · π, „√(x+1)".
            $radicand = $this->atom();
            if ($radicand < 0) {
                throw new \UnexpectedValueException;
            }

            return sqrt($radicand);
        }

        if (is_numeric($t)) {
            return (float) $t;
        }

        throw new \UnexpectedValueException;
    }

    private function startsAtom(string $t): bool
    {
        return in_array($t, ['(', 'π', '√', 'e'], true) || is_numeric($t);
    }
}
