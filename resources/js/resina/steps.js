/*
 * Step helpers ported from the prototype. Imported steps already carry
 * optional / technique / coverage; these functions compute them for the
 * automatic recipes and prefill them in the recipe editor.
 */
import { brushLabel, pickBrush } from './brushes.js';
import { isMetal } from './color.js';

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

/*
 * ---- Painting mode (version 2) -------------------------------------
 * guides: { code: { name, preparation[], steps[], wait_minutes, result,
 * mistakes[] } }, titles: [{ pattern, title }] in order, texts:
 * { key: lines[] } — as ClientPayload::techniqueGuides() sends them.
 */

/* A plain title for a role: the first matching pattern, otherwise the role itself without "1. ". */
export function simpleTitle(role, titles) {
    const text = String(role || '');
    for (const t of titles || []) {
        if (new RegExp(t.pattern, 'i').test(text)) return t.title;
    }
    return text.replace(/^\d+\.\s*/, '');
}

/*
 * What progress counts in a list of steps: every step that isn't
 * optional, but the alternatives of a choice group (the iris colors)
 * count as one. Each unit lists the indexes of its steps.
 */
export function progressUnits(steps) {
    const units = [];
    const groups = {};
    steps.forEach((step, index) => {
        if (step.optional) return;
        if (step.choice_group) {
            if (!groups[step.choice_group]) units.push((groups[step.choice_group] = []));
            groups[step.choice_group].push(index);
        } else {
            units.push([index]);
        }
    });
    return units;
}

/* The user's small drybrush, for the light-metallic alternative. */
function smallDrybrushText(brushList) {
    const drybrushes = (brushList || []).filter((b) => b.type === 'drybrush');
    const brush = drybrushes.length ? pickBrush('dry-s', drybrushes) : null;
    return brush ? 'il ' + brushLabel(brush) : 'un drybrush piccolo';
}

/*
 * Everything the step card of the painting mode says, beyond colors and
 * brush: title, how to prepare (the metallic variant replaces the
 * technique's own preparation), how it's done, wait, result, mistakes,
 * and the notes that apply (Shade TMM, the drybrush alternative for
 * metallic lights, washing the brush after metallics).
 */
export function stepGuide(step, { guides = {}, texts = {}, titles = [], palette, brushes: brushList = [] }) {
    const guide = (step.technique && guides[step.technique]) || null;
    const mix = step.mix || {};
    const metallic = palette ? isMetal(mix, palette) : false;
    const hasShade = palette ? Object.keys(mix).some((key) => palette.byKey[key] && palette.byKey[key].wash) : false;

    return {
        title: simpleTitle(step.role, titles),
        role: step.role,
        technique: guide ? guide.name : null,
        metallic,
        preparation: metallic ? texts.metallico_preparazione || [] : guide ? guide.preparation : [],
        how: guide ? guide.steps : [],
        waitMinutes: guide ? guide.wait_minutes : null,
        result: guide ? guide.result : null,
        mistakes: guide ? guide.mistakes : [],
        shadeNote: hasShade ? (texts.shade_tmm || [])[0] || null : null,
        lightAlternative: metallic && /^luce/i.test(step.role || '')
            ? ((texts.light_metallico_drybrush || [])[0] || '').replace('{pennello}', smallDrybrushText(brushList)) || null
            : null,
        after: metallic ? (texts.metallico_dopo || [])[0] || null : null,
    };
}
