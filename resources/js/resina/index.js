/*
 * Entry point of the "3D - Resina" pages (loaded only inside the
 * module). Alpine comes from Livewire, so components are registered on
 * alpine:init. Page data arrives as JSON in a <script> tag, read once.
 */
import * as color from './color.js';
import * as steps from './steps.js';
import * as brushes from './brushes.js';
import { runMixJobs } from './mix-jobs.js';

window.Resina = { color, steps, brushes };

function readPayload(id) {
    const node = document.getElementById(id);
    return node ? JSON.parse(node.textContent) : {};
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function sendJson(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!response.ok) throw new Error('HTTP ' + response.status);
    return response.json();
}

/*
 * The zone a recipe is painted on when shown in the Ricettario: it
 * drives the brush size (recipeHTML() in the prototype).
 */
export function recipeZone(recipe) {
    return { name: recipe.title, tab: recipe.category === 'basette' ? 'basetta' : recipe.category === 'occhi' ? 'volto' : '' };
}

export function paintStyle(paint) {
    return color.swStyle(paint.lin, paint.metal);
}

/*
 * Everything a step row shows, computed once: the templates only read
 * these fields. step = { role, usage, optional, technique, coverage,
 * mix, d? } (d = ΔE for automatic steps).
 */
export function decorateStep(step, zone, palette, brushList, urls) {
    const technique = step.technique && steps.TECHNIQUES[step.technique];
    const chip = brushes.brushChip(step, zone, brushList, palette);
    const mixKeys = Object.keys(step.mix || {}).filter((key) => palette.byKey[key]);

    return {
        role: step.role,
        usage: step.usage,
        optional: !!step.optional,
        swatch: color.mixStyle(step.mix || {}, palette),
        hex: color.mixHex(step.mix || {}, palette),
        lightness: color.mixLightness(step.mix || {}, palette),
        ingredients: mixKeys.map((key) => {
            const paint = palette.byKey[key];
            return { key, drops: color.drops(step.mix[key]), name: paint.name, code: paint.code, style: paintStyle(paint) };
        }),
        technique: technique ? { label: technique.label, href: urls.techniques ? urls.techniques + '#' + technique.anchor : null } : null,
        brush: { label: chip.label + (chip.metallicNote ? ' · solo metallici' : ''), title: chip.title, href: urls.brushes },
        coverage: step.coverage != null ? { pct: step.coverage, label: steps.coverageLabel(step.coverage) } : null,
        closeness: step.d != null ? color.closeness(step.d) : null,
        mixerHref: urls.mixer && mixKeys.length ? urls.mixer + '?mix=' + encodeURIComponent(JSON.stringify(step.mix)) : null,
    };
}

/* The "toni" scale: the same steps, darkest to lightest. */
export function toneScale(decorated) {
    return decorated
        .map((s) => ({ style: s.swatch, title: s.role, lightness: s.lightness }))
        .sort((a, b) => a.lightness - b.lightness);
}

function recipeBook(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);

    const recipes = data.recipes.map((recipe) => {
        const zone = recipeZone(recipe);
        const view = recipe.steps.map((step) => decorateStep(step, zone, palette, data.brushes, data.urls));
        const search = [
            recipe.title,
            recipe.who,
            recipe.tip,
            ...recipe.steps.map((s) => s.role + ' ' + Object.keys(s.mix).map((k) => (palette.byKey[k] ? palette.byKey[k].name + ' ' + palette.byKey[k].code : '')).join(' ')),
        ].join(' ').toLowerCase();

        return {
            slug: recipe.slug,
            title: recipe.title,
            who: recipe.who,
            tip: recipe.tip,
            category: recipe.category,
            view,
            scale: toneScale(view),
            search,
            editHref: data.editUrl ? data.editUrl.replace('__SLUG__', recipe.slug) : null,
        };
    });

    return {
        recipes,
        categories: [{ slug: 'tutte', name: 'Tutte' }, ...data.categories],
        category: 'tutte',
        query: '',

        init() {
            const hash = window.location.hash.slice(1);
            if (hash) {
                this.$nextTick(() => document.getElementById(hash)?.scrollIntoView({ block: 'start' }));
            }
        },

        visible(recipe) {
            const q = this.query.trim().toLowerCase();
            return (this.category === 'tutte' || recipe.category === this.category) && (!q || recipe.search.includes(q));
        },

        get visibleCount() {
            return this.recipes.filter((r) => this.visible(r)).length;
        },
    };
}

function blankStep() {
    return { uid: Math.random().toString(36).slice(2), role: '', usage: '', optional: false, technique: '', coverage: '', paints: [{ paint_id: '', drops: 1 }] };
}

/*
 * Recipe editor (admin). Steps keep the order shown: move them up and
 * down; that order is the painting order saved on submit.
 */
function recipeEditor(payloadId) {
    const data = readPayload(payloadId);
    const catalog = data.paints.filter((p) => !p.custom);
    const palette = color.createPalette(catalog);

    const groups = [];
    catalog.forEach((p) => {
        let group = groups.find((g) => g.line === p.line);
        if (!group) groups.push((group = { line: p.line, paints: [] }));
        group.paints.push({ id: p.id, label: p.name + ' · ' + p.code });
    });

    // Old input after a failed validation comes back as strings.
    const initial = (data.steps || []).map((s) => ({
        uid: Math.random().toString(36).slice(2),
        role: s.role ?? '',
        usage: s.usage ?? '',
        optional: s.optional === true || s.optional === '1' || s.optional === 1,
        technique: s.technique ?? '',
        coverage: s.coverage ?? '',
        paints: (s.paints || []).map((p) => ({ paint_id: String(p.paint_id ?? ''), drops: Number(p.drops) || 1 })),
    }));

    return {
        steps: initial.length ? initial : [blankStep()],
        paintGroups: groups,
        techniques: Object.entries(steps.TECHNIQUES).map(([code, t]) => ({ code, label: t.label })),

        mixOf(step) {
            const mix = {};
            step.paints.forEach((p) => {
                if (p.paint_id) mix['p' + p.paint_id] = (mix['p' + p.paint_id] || 0) + Number(p.drops || 0);
            });
            return mix;
        },
        swatch(step) {
            return color.mixStyle(this.mixOf(step), palette);
        },
        hex(step) {
            return color.mixHex(this.mixOf(step), palette);
        },
        get scale() {
            return this.steps
                .map((s) => ({ style: this.swatch(s), lightness: color.mixLightness(this.mixOf(s), palette), title: s.role }))
                .sort((a, b) => a.lightness - b.lightness);
        },

        /* Role (or usage) typed: prefill what the prototype derives from it. */
        roleChanged(step) {
            step.optional = steps.isOptionalRole(step.role);
            step.technique = steps.techniqueOf(step.role, step.usage) ?? '';
            const coverage = steps.coverageOf(step.role);
            step.coverage = coverage ?? '';
        },
        usageChanged(step) {
            step.technique = steps.techniqueOf(step.role, step.usage) ?? '';
        },

        addStep() {
            this.steps.push(blankStep());
        },
        removeStep(index) {
            this.steps.splice(index, 1);
        },
        moveStep(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.steps.length) return;
            const [step] = this.steps.splice(index, 1);
            this.steps.splice(target, 0, step);
        },
        addPaint(step) {
            step.paints.push({ paint_id: '', drops: 1 });
        },
        removePaint(step, index) {
            step.paints.splice(index, 1);
        },
    };
}

function brushKit(payloadId) {
    const data = readPayload(payloadId);

    return {
        brushes: data.brushes,
        types: Object.entries(brushes.BRUSH_TYPES).map(([code, label]) => ({ code, label })),
        busy: false,
        error: null,

        get slots() {
            return Object.entries(brushes.SLOTS).map(([key, slot]) => {
                const b = brushes.pickBrush(key, this.brushes);
                return { key, name: slot.n, description: slot.d, brush: b ? brushes.brushLabel(b) : null, substitute: !!b && b.type !== slot.t };
            });
        },

        async request(method, url, body) {
            this.busy = true;
            this.error = null;
            try {
                this.brushes = (await sendJson(method, url, body)).brushes;
            } catch (e) {
                this.error = 'Non sono riuscito a salvare: ricarica la pagina e riprova.';
            } finally {
                this.busy = false;
            }
        },
        brushUrl(brush) {
            return data.urls.update.replace('__ID__', brush.id);
        },
        save(brush, field, value) {
            return this.request('PATCH', this.brushUrl(brush), { [field]: value });
        },
        add() {
            return this.request('POST', data.urls.store);
        },
        remove(brush) {
            if (!confirm('Togliere ' + brushes.brushLabel(brush) + ' dal tuo elenco?')) return;
            return this.request('DELETE', this.brushUrl(brush));
        },
        reset() {
            if (!confirm("Ripristinare l'elenco preimpostato del kit Nicpro?")) return;
            return this.request('POST', data.urls.reset);
        },
    };
}

/*
 * Suggested paints to buy, each with the closest mix you can already
 * make. The search is heavy (triples of paints): it runs in the mix
 * worker, and each card shows «Calcolo…» until its own result lands.
 */
function shopSuggestions(payloadId) {
    const data = readPayload(payloadId);
    const owned = data.paints.filter((p) => p.owned);
    const inventory = color.createPalette(owned);

    return {
        suggestions: data.suggestions.map((s) => ({ ...s, best: null })),

        init() {
            const jobs = this.suggestions.filter((s) => !s.owned).map((s) => ({ id: s.code, kind: 'find', hex: s.hex }));

            runMixJobs(owned, jobs, (code, list) => {
                const best = list[0];
                const suggestion = this.suggestions.find((s) => s.code === code);
                if (!suggestion || !best) return;
                suggestion.best = {
                    closeness: color.closeness(best.d),
                    ingredients: Object.keys(best.mix).map((key) => {
                        const paint = inventory.byKey[key];
                        return { key, drops: color.drops(best.mix[key]), name: paint.name, code: paint.code, style: paintStyle(paint) };
                    }),
                };
            });
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('resinaRecipeBook', recipeBook);
    window.Alpine.data('resinaRecipeEditor', recipeEditor);
    window.Alpine.data('resinaBrushKit', brushKit);
    window.Alpine.data('resinaShopSuggestions', shopSuggestions);
});
