/*
 * Brush choice, ported from the prototype (SLOTS, pickBrush, zoneSize,
 * brushFor, brushChip). Brushes are { id, type, size, metallic_only }.
 * A zone is { name, tab } ("tab" as in the character sheet tabs).
 */
import { isMetal } from './color.js';
import { TECHNIQUES } from './steps.js';

export const BRUSH_TYPES = { tondo: 'Tondo', liner: 'Liner', spot: 'Spot Detail', piatto: 'Piatto', angolato: 'Angolato', drybrush: 'Drybrush' };

export const SLOTS = {
    spot: { t: 'spot', v: -19, n: 'Spot Detail', d: 'Il più fine di tutti: pupille, riflessi negli occhi, punti luce, nei.' },
    micro: { t: 'tondo', v: -4, n: 'Tondo micro', d: 'Contorno e iride degli occhi, puntini, dettagli minuscoli.' },
    fine: { t: 'tondo', v: -2, n: 'Tondo fine', d: 'Spigoli, luci estreme, ombre profonde, dettagli piccoli, labbra.' },
    medio: { t: 'tondo', v: 1, n: 'Tondo medio', d: 'Basi, ombre e luci su pelle, capelli, tute. È il pennello che userai di più.' },
    grande: { t: 'tondo', v: 2, n: 'Tondo grande', d: 'Basi e wash su superfici grandi: mantelli, armature, basette.' },
    liner: { t: 'liner', v: -2, n: 'Liner', d: 'Linee lunghe e sottili: ragnatele, venature del marmo, contorni lunghi, cuciture.' },
    piatto: { t: 'piatto', v: 2, n: 'Piatto', d: 'Velature su superfici piatte e larghe (scudi, mantelli, placche).' },
    angolato: { t: 'angolato', v: 1, n: 'Angolato', d: 'Spigoli dritti e lunghi: bordi di placche, scudi e basette.' },
    'dry-s': { t: 'drybrush', v: 3, n: 'Drybrush piccolo', d: 'Drybrush su capelli, piume, catene, bordi delle armature.' },
    'dry-m': { t: 'drybrush', v: 6, n: 'Drybrush medio', d: 'Drybrush su armature intere, mantelli, rocce piccole.' },
    'dry-l': { t: 'drybrush', v: 9, n: 'Drybrush grande', d: 'Drybrush su rocce, terra, neve, basette.' },
};

/* Numeric size of a round brush: 10/0 → -9, 0 → 0, 2 → 2. */
export function bval(s) {
    s = String(s || '').trim().toUpperCase();
    const m = /^(\d+)\s*\/\s*0$/.exec(s);
    if (m) {
        const n = +m[1];
        return n <= 1 ? 0 : -(n - 1);
    }
    if (s === '00') return -1;
    if (s === '000') return -2;
    const x = parseFloat(s.replace(',', '.'));
    return isNaN(x) ? 0 : x;
}

/* Numeric size of a drybrush: XS…XL or the printed number. */
export function dval(s) {
    const u = String(s || '').toUpperCase(), m = { XS: 1, S: 3, M: 5, L: 7, XL: 9 };
    return m[u] != null ? m[u] : parseFloat(u) || 5;
}

export function brushLabel(b) {
    return BRUSH_TYPES[b.type] + ' ' + b.size;
}

export function pickBrush(slot, brushes) {
    const S = SLOTS[slot];
    let list = brushes.filter((b) => b.type === S.t);
    if (!list.length) {
        if (S.t === 'liner' || S.t === 'angolato') return pickBrush('fine', brushes);
        if (S.t === 'spot') return pickBrush('micro', brushes);
        if (S.t === 'piatto') return pickBrush('grande', brushes);
        list = brushes.filter((b) => b.type === 'tondo');
        if (!list.length) return null;
    }
    const f = S.t === 'drybrush' ? dval : bval;
    return list.slice().sort((a, b) => Math.abs(f(a.size) - S.v) - Math.abs(f(b.size) - S.v))[0];
}

export function metalBrush(brushes) {
    return brushes.find((b) => b.metallic_only);
}

/* "l" large, "s" small or "m" medium area, from the zone's tab and name. */
export function zoneSize(z) {
    if (!z) return 'm';
    const n = (z.name || '').toLowerCase();
    if (z.tab === 'basetta' || /mantello|cloth|surplice|scaglia|armatura|roccia|terra|marmo|neve|lava|sabbia|erba|pantaloni|tuta|vestito|abito|scudo|ali\b|pietra|colonne/.test(n)) return 'l';
    if (z.tab === 'volto' || /occhi|gemm|neo|puntini|punto|unghia|cicatrice|stella|visore|lenti|rosario|labbra|catene|reattore|ragnatela|maschera/.test(n)) return 's';
    return 'm';
}

/* { slot, b: brush or null, why } for a step { role, usage, technique }. */
export function brushFor(step, z, brushes) {
    const r = String(step.role || '').toLowerCase(), u = String(step.usage || '').toLowerCase();
    const tk = step.technique && TECHNIQUES[step.technique] ? TECHNIQUES[step.technique].label : '';
    const sz = zoneSize(z);
    let slot, why;
    if (tk === 'drybrush') {
        slot = z && z.tab === 'basetta' ? 'dry-l' : sz === 'l' ? 'dry-m' : 'dry-s';
        why = 'Pennello quasi asciutto, colpi leggeri avanti e indietro.';
    } else if (tk === 'wash') {
        slot = sz === 's' ? 'fine' : sz === 'l' ? 'grande' : 'medio';
        why = 'Deve tenere tanto liquido: caricalo bene.';
    } else if (tk === 'velatura') {
        slot = sz === 'l' ? 'piatto' : 'medio';
        why = 'Passate larghe e trasparenti, pennello umido ma non gocciolante.';
    } else if (/linee|venature|ragnatela/.test(r + ' ' + u)) {
        slot = 'liner';
        why = 'Le linee lunghe vengono più regolari con un liner.';
    } else if (tk === 'luce sugli spigoli') {
        slot = sz === 's' ? 'micro' : sz === 'l' ? 'angolato' : 'fine';
        why = 'Appoggia il fianco della punta sul bordo.';
    } else if (/^(pupilla|riflesso|punt[oi] luce|punto|neo|puntini)/.test(r)) {
        slot = 'spot';
        why = 'Il pennello più fine: tocca appena la superficie.';
    } else if (tk === 'punta del pennello') {
        slot = 'micro';
        why = 'Solo la punta, colore ben diluito.';
    } else if (/^(ombra profonda|luce estrema)/.test(r)) {
        slot = sz === 's' ? 'micro' : 'fine';
        why = 'Aree piccole: serve precisione.';
    } else if (tk === 'strato coprente') {
        slot = sz === 'l' ? 'grande' : sz === 's' ? 'fine' : 'medio';
        why = 'Strati sottili e uniformi, due o tre mani.';
    } else if (tk === 'strato sottile') {
        slot = sz === 's' ? 'fine' : 'medio';
        why = 'Serve controllo sui bordi della zona.';
    } else {
        slot = sz === 's' ? 'micro' : 'fine';
        why = 'Lavoro di dettaglio.';
    }
    return { slot, b: pickBrush(slot, brushes), why };
}

/*
 * What the step's brush tag shows: the chosen brush (or the slot name
 * when the kit has none), swapped for the metallic-only brush on
 * metallic steps. { label, title, metallicNote }.
 */
export function brushChip(step, z, brushes, palette) {
    const bf = brushFor(step, z, brushes);
    const metal = !!step.mix && isMetal(step.mix, palette);
    const mb = metal && metalBrush(brushes) && bf.b && bf.b.type === 'tondo' ? metalBrush(brushes) : null;
    const b = mb || bf.b;
    return {
        label: b ? brushLabel(b) : SLOTS[bf.slot].n,
        title: bf.why + (metal ? ' Metallico: usa un pennello che tieni solo per i metallici.' : ''),
        metallicNote: metal && !mb,
        slot: bf.slot,
        brush: b,
    };
}
