export type ItemType =
    | 'mcq' | 'match' | 'numeric' | 'fill_blanks'
    | 'next_step' | 'order_steps' | 'find_error'
    | 'timeline' | 'label_diagram' | 'balance_equation'
    | 'build_formula' | 'code_output';

export interface Choice {
    key: string;
    text?: string;
    latex?: string;
}

/**
 * Forma itemului AȘA CUM O VEDE CLIENTUL.
 *
 * Observă ce nu există aici: `answer`, `explanation`, statistici de dificultate.
 * Nu e o omisiune — serverul nu le trimite niciodată înainte de submit
 * (App\Http\Resources\ItemForStudentResource).
 */
export interface StudentItem {
    uuid: string;
    type: ItemType;
    points: number;
    position: number | null;

    /**
     * Doar numărătoarea. TEXTUL indiciilor nu ajunge aici — se cere unul câte
     * unul de la server, ca penalizarea de XP să fie reală.
     */
    hints: { allowed: boolean; total: number; revealed: number };

    skill: { id: number; name: string } | null;
    payload: {
        stem?: string;
        stem_latex?: string;
        stem_template?: string;
        choices?: Choice[];
        steps?: Choice[];
        left?: Choice[];
        right?: Choice[];
        blanks?: { key: string; options?: string[] }[];
        problem?: string;
        solved_steps?: string[];
        unit?: string;
        /** Text-suport pentru comprehensiune (Subiectul I la limbi străine). */
        passage?: string;
        passage_title?: string;
        code?: string;
        /** Variante de cod per limbaj: examenul acceptă Pascal sau C/C++. */
        code_variants?: Record<string, string>;
        image?: string;
    };
}

/**
 * Răspunsul elevului. Fiecare tip de item își compune propria formă.
 *
 * Tipul e recursiv, nu `Record<string, unknown>`, ca să fie direct compatibil cu
 * `FormDataConvertible` din Inertia — altfel fiecare trimitere de răspuns ar avea
 * nevoie de un cast.
 */
export type AnswerValue =
    | string
    | number
    | boolean
    | null
    | AnswerValue[]
    | { [key: string]: AnswerValue };

export type StudentAnswer = { [key: string]: AnswerValue };

/**
 * Ce citesc componentele de item din feedback: dacă e corect și cheia, în
 * jetoanele cu care au fost afișate variantele. Pagina de rezultat a
 * simulării le refolosește fără XP sau rating, care acolo nu există.
 */
export type ItemFeedback = Pick<Feedback, 'is_correct' | 'score' | 'breakdown' | 'correct_answer'>;

export interface Feedback {
    is_correct: boolean;
    score: number;
    breakdown: Record<string, unknown>;
    correct_answer: Record<string, unknown> | null;
    explanation: string | null;
    xp: { amount: number; reasons: Record<string, unknown>; capped: boolean };
    rating_delta: number | null;
    server_ms: number | null;
    totals: Gamification;
    is_duplicate: boolean;
}

export interface Gamification {
    xp: number;
    level: number;
    rating: number;
    streak_days: number;
    xp_today: number;
    daily_cap: number;
}

export interface RevealedHint {
    index: number;
    text: string;
    revealed: number;
    total: number;
    has_more: boolean;
}

export interface AuthUser {
    id: number;
    username: string;
    avatar: string | null;
    avatar_url: string | null;
    public_alias: string | null;
    username_pending: boolean;
    avatar_pending: boolean;
    role: string;
    locale: string;
    profile: string | null;
    exam_target: string | null;
    is_premium: boolean;
}
