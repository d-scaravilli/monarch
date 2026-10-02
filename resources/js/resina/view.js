/*
 * Shared helpers of the "3D - Resina" pages: page data, JSON requests,
 * and the "decorated" shapes the Blade templates read (step rows,
 * recipe cards), computed once so the templates never do math.
 */
import * as color from './color.js';
import * as steps from './steps.js';
import * as brushes from './brushes.js';

export function readPayload(id) {
    const node = document.getElementById(id);
    return node ? JSON.parse(node.textContent) : {};
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function sendJson(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return response.json();
}

export function uid() {
    return Math.random().toString(36).slice(2);
}

export function paintStyle(paint) {
    return color.swStyle(paint.lin, paint.metal);
}

/* A paint as an ingredient chip: dot, drops, name and code. */
export function ingredient(key, drops, palette) {
    const paint = palette.byKey[key];
    return { key, drops: color.drops(drops), name: paint.name, code: paint.code, style: paintStyle(paint) };
}

/*
 * The zone a recipe is painted on when shown on its own (Ricettario,
 * project recipes): it drives the brush size (recipeHTML()).
 */
export function recipeZone(recipe) {
    return { name: recipe.title, tab: recipe.category === 'basette' ? 'basetta' : recipe.category === 'occhi' ? 'volto' : '' };
}

/*
 * Everything a step row shows, computed once. step = { role, usage,
 * optional, technique, coverage, mix, d? } (d = ΔE for automatic steps).
 */
export function decorateStep(step, zone, palette, brushList, urls) {
    const technique = step.technique && steps.TECHNIQUES[step.technique];
    const chip = brushes.brushChip(step, zone, brushList, palette);
    const mix = step.mix || {};
    const mixKeys = Object.keys(mix).filter((key) => palette.byKey[key]);

    return {
        role: step.role,
        usage: step.usage,
        optional: !!step.optional,
        choice: !!step.choice_group,
        swatch: color.mixStyle(mix, palette),
        hex: color.mixHex(mix, palette),
        lightness: color.mixLightness(mix, palette),
        ingredients: mixKeys.map((key) => ingredient(key, mix[key], palette)),
        technique: technique ? { label: technique.label, href: urls.techniques ? urls.techniques + '#' + technique.anchor : null } : null,
        brush: { label: chip.label + (chip.metallicNote ? ' · solo metallici' : ''), title: chip.title, href: urls.brushes },
        coverage: step.coverage != null ? { pct: step.coverage, label: steps.coverageLabel(step.coverage) } : null,
        closeness: step.d != null ? color.closeness(step.d) : null,
        mixerHref: urls.mixer && mixKeys.length ? urls.mixer + '?mix=' + encodeURIComponent(JSON.stringify(mix)) : null,
    };
}

/*
 * Automatic steps (autoBSL) and guide steps carry no stored optional /
 * technique / coverage: derive them from the role like the prototype.
 */
export function withDerivedFields(step) {
    return {
        ...step,
        optional: steps.isOptionalRole(step.role),
        technique: steps.techniqueOf(step.role, step.usage),
        coverage: steps.coverageOf(step.role),
    };
}

/* The "toni" scale: the same steps, darkest to lightest. */
export function toneScale(decorated) {
    return decorated.map((s) => ({ style: s.swatch, title: s.role, lightness: s.lightness })).sort((a, b) => a.lightness - b.lightness);
}

/* A recipe card of the Ricettario (and of a project's recipes tab). */
export function decorateRecipe(recipe, palette, brushList, urls, editUrl) {
    const zone = recipeZone(recipe);
    const view = recipe.steps.map((step) => decorateStep(step, zone, palette, brushList, urls));
    const search = [
        recipe.title,
        recipe.who,
        recipe.tip,
        ...recipe.steps.map((s) => s.role + ' ' + Object.keys(s.mix).map((k) => (palette.byKey[k] ? palette.byKey[k].name + ' ' + palette.byKey[k].code : '')).join(' ')),
    ]
        .join(' ')
        .toLowerCase();

    return {
        slug: recipe.slug,
        title: recipe.title,
        who: recipe.who,
        tip: recipe.tip,
        category: recipe.category,
        view,
        scale: toneScale(view),
        search,
        editHref: editUrl ? editUrl.replace('__SLUG__', recipe.slug) : null,
    };
}
