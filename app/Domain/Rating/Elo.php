<?php

namespace App\Domain\Rating;

/**
 * Elo simetric între ELEV și ITEM.
 *
 * Aproape toate platformele dau rating doar utilizatorilor. Rating-ul pe item
 * e cel care aduce lucrurile valoroase:
 *
 *   - dificultate adaptivă reală: servim itemi la ~70% șansă de succes, zona
 *     optimă de învățare;
 *   - matchmaking corect în dueluri;
 *   - detectarea automată a itemilor stricați — dacă elevii cu rating mare pică
 *     sistematic un item „ușor", itemul e ambiguu sau are cheia greșită;
 *   - estimare de notă care are o bază matematică, nu decorativă.
 */
final class Elo
{
    /** Șansa ca un elev cu rating $userRating să rezolve un item cu rating $itemRating. */
    public function expectedScore(float $userRating, float $itemRating): float
    {
        return 1 / (1 + 10 ** (($itemRating - $userRating) / 400));
    }

    public function apply(
        int $userRating,
        float $itemRating,
        float $score,
        int $userAttempts,
        int $itemAttempts,
    ): EloResult {
        $expected = $this->expectedScore($userRating, $itemRating);

        // K mai mare cât timp nu știm încă unde se află elevul / itemul: calibrăm
        // repede la început, apoi stabilizăm.
        $userK = $this->kFactor($userAttempts, 40, 16);
        $itemK = $this->kFactor($itemAttempts, 32, 4);

        $userAfter = (int) round($userRating + $userK * ($score - $expected));
        $itemAfter = $itemRating + $itemK * ($expected - $score);

        return new EloResult(
            userRatingBefore: $userRating,
            // Rating-ul nu coboară sub 100: elevul care învață nu trebuie
            // pedepsit până la un număr descurajant.
            userRatingAfter: max(100, $userAfter),
            itemRatingBefore: $itemRating,
            itemRatingAfter: max(200, min(2800, $itemAfter)),
            expectedScore: $expected,
        );
    }

    private function kFactor(int $attempts, int $provisional, int $settled): int
    {
        return match (true) {
            $attempts < 20 => $provisional,
            $attempts < 80 => (int) round(($provisional + $settled) / 2),
            default => $settled,
        };
    }
}
