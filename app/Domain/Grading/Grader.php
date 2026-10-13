<?php

namespace App\Domain\Grading;

use App\Models\Item;

interface Grader
{
    /**
     * Corectează răspunsul elevului.
     *
     * Rulează EXCLUSIV pe server. Cheia de corectare (`$item->gradingKey()`) nu
     * ajunge niciodată la client înainte de submit.
     *
     * @param  array  $submitted  Răspunsul trimis de elev, deja validat structural.
     */
    public function grade(Item $item, array $submitted): GradeResult;
}
