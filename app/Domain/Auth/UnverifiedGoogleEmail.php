<?php

namespace App\Domain\Auth;

use RuntimeException;

/**
 * Google a întors o adresă pe care NU a verificat-o, iar la noi există deja un
 * cont cu ea.
 *
 * Legarea ar însemna preluarea acelui cont de către oricine își poate declara
 * adresa altcuiva. Se prinde separat de RuntimeException, ca un catch larg să nu
 * o transforme într-un mesaj liniștitor (§10g).
 */
final class UnverifiedGoogleEmail extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Google nu a confirmat această adresă de email. '
            .'Intră cu parola, apoi leagă contul din Setări.'
        );
    }
}
