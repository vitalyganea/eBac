<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Butonul de urgență pentru clienții mobili.
 *
 * Pe web deployezi și în 5 minute toată lumea e pe versiunea nouă. Pe mobil,
 * versiunea de acum 8 luni rulează încă pe telefonul cuiva care nu dă update
 * niciodată — și nu poți forța. Fără acest gate, prima schimbare incompatibilă
 * sparge aplicația pentru mii de elevi în plin sezon de BAC, fără soluție.
 */
class EnsureMinimumAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $min = config('ebac.min_mobile_version');
        $client = $request->header('X-App-Version');

        // Clientul web nu trimite antetul; el e mereu la zi.
        if ($min === null || $client === null) {
            return $next($request);
        }

        if (version_compare($client, $min, '<')) {
            return response()->json([
                'error' => 'upgrade_required',
                'message' => 'Actualizează aplicația pentru a continua.',
                'min_version' => $min,
            ], Response::HTTP_UPGRADE_REQUIRED);
        }

        return $next($request);
    }
}
