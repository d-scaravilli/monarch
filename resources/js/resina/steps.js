/*
 * Step helpers ported from the prototype. Imported steps already carry
 * optional / technique / coverage; these functions compute them for the
 * automatic recipes and prefill them in the recipe editor.
 */

/* Technique code → label and anchor on the "Tecniche" page. */
export const TECHNIQUES = {
    coprente: { label: 'strato coprente', anchor: 't-diluire' },
    sottile: { label: 'strato sottile', anchor: 't-bsl' },
    punta: { label: 'punta del pennello', anchor: 't-occhi' },
    velatura: { label: 'velatura', anchor: 't-velatura' },
    spigoli: { label: 'luce sugli spigoli', anchor: 't-spigoli' },
    wash: { label: 'wash', anchor: 't-wash' },
    drybrush: { label: 'drybrush', anchor: 't-drybrush' },
};

export function isOptionalRole(role) {
    return /facoltativ|^ombra profonda|^luce estrema|^punt[oi] luce|^variante/i.test(role);
}

/* Technique code from role and usage (techOf() in the prototype). */
export function techniqueOf(role, usage) {
    const r = String(role || '').toLowerCase(), t = r + ' ' + String(usage || '').toLowerCase();
    if (/wash|shade/.test(r)) return 'wash';
    if (/drybrush/.test(t)) return 'drybrush';
    if (/velatura/.test(t)) return 'velatura';
    if (/^spigoli/.test(r) || /linea sottil/.test(t)) return 'spigoli';
    if (/^(punt[oi] luce|riflesso|pupilla|punto|neo|puntini|contorno|linea)/.test(r)) return 'punta';
    if (/^(base|prima mano|fondo|mix|ripresa|recupera)/.test(r)) return 'coprente';
    if (/^(ombra|luce)/.test(r)) return 'sottile';
    return null;
}

/* Rough share of the zone a step covers, in percent (or null). */
export function coverageOf(role) {
    const r = String(role || '').toLowerCase();
    if (/wash|velatura/.test(r)) return null;
    if (/^(base|prima mano|fondo|mix)/.test(r)) return 100;
    if (/^ombra profonda/.test(r)) return 10;
    if (/^ombra/.test(r)) return 35;
    if (/^(ripresa|recupera|luce intermedia)/.test(r)) return 50;
    if (/^luce estrema/.test(r)) return 10;
    if (/^luce/.test(r)) return 30;
    if (/^spigoli/.test(r)) return 5;
    if (/^punt/.test(r)) return 3;
    if (/drybrush/.test(r)) return 40;
    return null;
}

export function coverageLabel(coverage) {
    return coverage === 100 ? 'copre tutta la zona' : 'copre circa il ' + coverage + '% della zona';
}

/*
 * The mix that best represents a recipe (its "Base" step), used for the
 * zone swatch and the closeness check (baseOf() in the prototype).
 */
export function baseMixOf(steps) {
    for (let i = 0; i < steps.length; i++) {
        if (/^base$/i.test(steps[i].role)) return steps[i].mix;
    }
    for (let i = 0; i < steps.length; i++) {
        if (/^(mix|base\b)/i.test(steps[i].role) && !/scura|facolt/i.test(steps[i].role)) return steps[i].mix;
    }
    return steps.length ? steps[0].mix : {};
}
