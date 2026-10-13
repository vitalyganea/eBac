<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Atribuie fiecărei scrieri numărul următor din secvența globală
 * `content_revision`.
 *
 * Ăsta e mecanismul care face posibil delta sync-ul: clientul (web offline sau,
 * mai târziu, aplicația mobilă) ține minte ultima revizie văzută și cere doar
 * diferența, în loc să redescarce tot conținutul:
 *
 *     GET /api/v1/content/manifest?since=1043
 *
 * Secvența e globală, nu per tabel, ca un singur cursor să acopere itemi,
 * competențe și videoclipuri deodată.
 */
trait TracksContentRevision
{
    public static function bootTracksContentRevision(): void
    {
        static::saving(function ($model) {
            // Nu bumpăm revizia dacă nu s-a schimbat nimic de conținut.
            if ($model->exists && $model->isClean(array_diff(array_keys($model->getDirty()), ['revision']))) {
                return;
            }

            $model->revision = DB::selectOne("select nextval('content_revision') as r")->r;
        });
    }
}
