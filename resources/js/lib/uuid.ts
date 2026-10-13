/**
 * UUID generat pe CLIENT pentru fiecare răspuns.
 *
 * E piesa care face endpointul de submit idempotent. Rețelele mobile duplică
 * request-uri constant; fără el, un elev care intră în tunel și retrimite
 * primește XP de două ori.
 *
 * Se generează O DATĂ per item afișat și se refolosește la fiecare reîncercare
 * a aceluiași răspuns — nu la fiecare apel de rețea.
 */
export function uuidv4(): string {
    const webCrypto: Crypto | undefined = typeof crypto !== 'undefined' ? crypto : undefined;

    if (webCrypto && typeof webCrypto.randomUUID === 'function') {
        return webCrypto.randomUUID();
    }

    // Fallback pentru WebView-uri vechi de Android, unde crypto.randomUUID lipsește.
    const bytes = new Uint8Array(16);

    if (webCrypto) {
        webCrypto.getRandomValues(bytes);
    } else {
        // Ultimă soluție: fără crypto nu avem entropie bună, dar un UUID de
        // idempotency nu are nevoie de garanții criptografice.
        for (let i = 0; i < bytes.length; i++) {
            bytes[i] = Math.floor(Math.random() * 256);
        }
    }

    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
