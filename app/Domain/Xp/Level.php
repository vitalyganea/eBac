<?php

namespace App\Domain\Xp;

/**
 * Curba de nivel, într-un singur loc.
 *
 * Era duplicată în SubmitAnswer și în RecomputeUser — două implementări ale
 * aceleiași formule, care puteau diverge la prima ajustare. Acum e folosită și
 * pentru nivelul PE DISCIPLINĂ, deci ar fi fost trei.
 *
 * Formula: nivelul n începe la (n-1)² × 100 XP.
 *   Nv. 2 →    100 XP
 *   Nv. 5 →  1.600 XP
 *   Nv. 10 →  8.100 XP
 *   Nv. 20 → 36.100 XP
 *
 * Încetinește progresiv: primele niveluri vin repede (elevul trebuie să simtă
 * mișcare în prima sesiune), următoarele cer efort real.
 */
final class Level
{
    public static function forXp(int $xp): int
    {
        return max(1, (int) floor(sqrt(max(0, $xp) / 100)) + 1);
    }

    /** XP-ul total necesar pentru a ATINGE nivelul dat. */
    public static function xpForLevel(int $level): int
    {
        return (max(1, $level) - 1) ** 2 * 100;
    }

    /**
     * Progresul în interiorul nivelului curent.
     *
     * @return array{level: int, xp: int, current: int, needed: int, percent: int}
     */
    public static function progress(int $xp): array
    {
        $xp = max(0, $xp);
        $level = self::forXp($xp);

        $floor = self::xpForLevel($level);
        $ceiling = self::xpForLevel($level + 1);

        $current = $xp - $floor;
        $needed = $ceiling - $floor;

        return [
            'level' => $level,
            'xp' => $xp,
            'current' => $current,
            'needed' => $needed,
            'percent' => $needed > 0 ? (int) round($current / $needed * 100) : 0,
        ];
    }
}
