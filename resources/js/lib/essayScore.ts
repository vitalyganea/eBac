/**
 * Punctajul autoevaluării, calculat în browser doar ca previzualizare.
 *
 * Totalul care se salvează îl calculează serverul (App\Domain\Essays\ScoreEssay);
 * regulile de aici sunt aceleași, ca elevul să-și vadă nota în timp ce bifează.
 */

export type Band = [number, number, number];

export interface Criterion {
    key: string;
    group: string;
    label: string;
    options: [number, string][];
}

export interface ErrorCriterion {
    key: string;
    label: string;
    max: number;
    per: number;
    caps: Band[] | null;
}

export interface Rubric {
    total: number;
    criteria: Criterion[];
    errors?: ErrorCriterion[];
    volume?: { max: number; over_max: number | 'max'; bands: Band[] };
}

/** „Orice semn separat prin spații albe se consideră cuvânt." (baremul ANCE) */
export function countWords(text: string): number {
    const t = text.trim();
    return t === '' ? 0 : t.split(/\s+/u).length;
}

function band(bands: Band[], words: number, overMax: number | 'max'): number {
    if (words > bands[0][1]) return overMax === 'max' ? bands[0][2] : overMax;
    for (const [min, max, points] of bands) {
        if (words >= min && words <= max) return points;
    }
    return 0;
}

export function errorPoints(e: ErrorCriterion, errors: number, words: number): number {
    const cap = e.caps === null ? e.max : band(e.caps, words, 'max');
    return Math.max(0, cap - Math.floor(errors / e.per));
}

export function volumePoints(rubric: Rubric, words: number): number {
    return rubric.volume ? band(rubric.volume.bands, words, rubric.volume.over_max) : 0;
}

export function preview(
    rubric: Rubric,
    criteria: Record<string, number | undefined>,
    errors: Record<string, number | undefined>,
    words: number,
): number {
    let total = 0;
    for (const c of rubric.criteria) total += criteria[c.key] ?? 0;
    for (const e of rubric.errors ?? []) total += errorPoints(e, errors[e.key] ?? 0, words);
    return total + volumePoints(rubric, words);
}
