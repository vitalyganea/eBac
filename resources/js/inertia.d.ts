import type { AuthUser, Gamification, RevealedHint } from './types';

/**
 * Props-urile partajate din App\Http\Middleware\HandleInertiaRequests.
 * Ținute sincronizate manual cu metoda share() de acolo.
 */
declare module '@inertiajs/core' {
    interface PageProps {
        auth: { user: AuthUser | null };
        gamification: Gamification | null;
        open_gaps: number;
        flash: {
            success: string | null;
            error: string | null;
            hint: RevealedHint | null;
        };
    }
}
