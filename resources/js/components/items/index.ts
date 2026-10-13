import type { Component } from 'vue';
import type { ItemType, StudentAnswer, StudentItem } from '@/types';
import McqItem from './McqItem.vue';
import OrderStepsItem from './OrderStepsItem.vue';
import MatchItem from './MatchItem.vue';
import NumericItem from './NumericItem.vue';
import FillBlanksItem from './FillBlanksItem.vue';

/**
 * 12 tipuri de item, 5 componente — oglinda exactă a GraderRegistry din PHP.
 *
 * „Găsește greșeala", „ce afișează codul" și „identifică pe schemă" sunt, ca
 * interacțiune, aceeași alegere dintre variante. Adăugarea unui mini-joc nou
 * pentru o materie nouă înseamnă de obicei ZERO cod nou aici.
 */
export const itemComponents: Record<ItemType, Component> = {
    mcq: McqItem,
    next_step: McqItem,
    find_error: McqItem,
    code_output: McqItem,
    label_diagram: McqItem,

    order_steps: OrderStepsItem,
    timeline: OrderStepsItem,
    build_formula: OrderStepsItem,

    match: MatchItem,
    numeric: NumericItem,

    fill_blanks: FillBlanksItem,
    balance_equation: FillBlanksItem,
};

export function componentFor(type: ItemType): Component {
    return itemComponents[type] ?? McqItem;
}

/**
 * Când poate fi trimis răspunsul.
 *
 * Ținut aici, lângă componentele de item, ca să nu se împrăștie prin pagini:
 * un tip nou își declară condiția de completitudine într-un singur loc.
 */
export function isAnswerComplete(item: StudentItem, answer: StudentAnswer | null): boolean {
    if (!answer) return false;

    switch (item.type) {
        case 'order_steps':
        case 'timeline':
        case 'build_formula': {
            const order = (answer.order as string[]) ?? [];
            return order.length === (item.payload.steps?.length ?? 0);
        }

        case 'match': {
            const pairs = (answer.pairs as Record<string, string>) ?? {};
            return Object.keys(pairs).length === (item.payload.left?.length ?? 0);
        }

        case 'numeric':
            return String(answer.value ?? '').trim() !== '';

        case 'fill_blanks':
        case 'balance_equation': {
            const values = (answer.values as Record<string, string>) ?? {};
            const blanks = item.payload.blanks ?? [];
            return blanks.length > 0 && blanks.every((b) => String(values[b.key] ?? '').trim() !== '');
        }

        default:
            return Boolean(answer.choice);
    }
}
