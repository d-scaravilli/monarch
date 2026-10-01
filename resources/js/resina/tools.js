/*
 * Trova colore, Mixer and Percorso (runFinder, the mixer and renderPath
 * in the prototype).
 */
import * as color from './color.js';
import { runMixJobs } from './mix-jobs.js';
import { decorateStep, ingredient, paintStyle, readPayload, sendJson, withDerivedFields } from './view.js';

function normalizeHex(value) {
    let v = String(value || '').trim();
    if (v && v[0] !== '#') v = '#' + v;
    return /^#[0-9a-fA-F]{6}$/.test(v) ? v.toUpperCase() : null;
}

/* The best paint to buy for a color, when the user doesn't own it yet. */
function buySuggestion(hex, suggestions) {
    const nearest = color.nearestBuy(hex, suggestions);
    return nearest && !nearest.b.owned ? nearest.b : null;
}

export function finder(payloadId) {
    const data = readPayload(payloadId);
    const owned = data.paints.filter((p) => p.owned);
    const inventory = color.createPalette(owned);

    return {
        hex: data.initialHex,
        hexInput: data.initialHex,
        three: true,
        presets: data.presets,
        results: [],
        buy: null,
        busy: false,
        runId: 0,

        init() {
            this.run();
        },

        /* The picker only updates the hex field; «Calcola» runs the search. */
        picked(value) {
            this.hexInput = value.toUpperCase();
            this.hex = this.hexInput;
        },
        hexTyped() {
            const hex = normalizeHex(this.hexInput);
            if (hex) this.choose(hex);
        },
        choose(hex) {
            this.hex = hex;
            this.hexInput = hex;
            this.run();
        },

        /* Heavy search: done in the worker, newest request wins. */
        run() {
            const id = ++this.runId;
            const target = this.hex;
            this.busy = true;
            runMixJobs(owned, [{ id, kind: 'find', hex: target, three: this.three }], (jobId, list) => {
                if (jobId !== this.runId) return;
                this.results = list.map((r) => ({
                    hex: r.hex,
                    closeness: color.closeness(r.d),
                    ingredients: Object.keys(r.mix).map((key) => ingredient(key, r.mix[key], inventory)),
                    mixerHref: data.urls.mixer + '?mix=' + encodeURIComponent(JSON.stringify(r.mix)),
                }));
                this.buy = list.length && list[0].d >= 8 ? buySuggestion(target, data.suggestions) : null;
                this.busy = false;
            });
        },
    };
}

const MAX_DROPS = 40;

export function mixer(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);
    const owned = color.createPalette(data.paints.filter((p) => p.owned));

    const groups = [];
    owned.list.forEach((p) => {
        let group = groups.find((g) => g.line === p.line);
        if (!group) groups.push((group = { line: p.line, paints: [] }));
        group.paints.push({ key: p.key, label: p.name + ' · ' + p.code });
    });

    // Opened from a step's «Mixer» (?mix=…) or a shelf bottle (?add=key).
    let mix = { ...data.mixer };
    const params = new URLSearchParams(window.location.search);
    let fromUrl = false;
    if (params.has('mix')) {
        try {
            mix = JSON.parse(params.get('mix'));
            fromUrl = true;
        } catch (e) {
            // Ignore a broken link.
        }
    }
    if (params.has('add') && palette.byKey[params.get('add')]) {
        mix[params.get('add')] = Math.min((mix[params.get('add')] || 0) + 1, MAX_DROPS);
        fromUrl = true;
    }
    Object.keys(mix).forEach((key) => {
        if (!palette.byKey[key]) delete mix[key];
    });

    let saveTimer = null;

    return {
        mix,
        saved: data.saved,
        paintGroups: groups,
        selected: owned.list[0]?.key ?? '',
        name: '',
        error: null,

        init() {
            if (fromUrl) {
                history.replaceState(null, '', window.location.pathname);
                this.persist();
            }
        },

        get rows() {
            return Object.keys(this.mix)
                .filter((key) => palette.byKey[key])
                .map((key) => {
                    const paint = palette.byKey[key];
                    return { key, name: paint.name, code: paint.code, style: paintStyle(paint), drops: color.drops(this.mix[key]) };
                });
        },

        /* The result: swatch, hex and what the prototype says about it. */
        get result() {
            const lin = color.mixLin(this.mix, palette);
            if (!lin) return null;
            const metal = color.isMetal(this.mix, palette);
            const keys = Object.keys(this.mix).filter((key) => palette.byKey[key]);
            const nearest = owned.list.length ? color.nearestPaint(lin, owned) : null;
            return {
                style: color.swStyle(lin, metal),
                hex: color.lin2hex(lin),
                ingredients: keys.map((key) => ingredient(key, this.mix[key], palette)),
                nearest: nearest ? { name: nearest.p.name, code: nearest.p.code, same: nearest.d < 4 } : null,
                wash: keys.some((key) => palette.byKey[key].wash),
                metal: metal ? 'result' : keys.some((key) => palette.byKey[key].metal) ? 'little' : null,
            };
        },

        add() {
            if (!this.selected) return;
            this.mix[this.selected] = Math.min((this.mix[this.selected] || 0) + 1, MAX_DROPS);
            this.persist();
        },
        change(key, delta) {
            const value = (this.mix[key] || 0) + delta;
            if (value <= 0) delete this.mix[key];
            else this.mix[key] = Math.min(value, MAX_DROPS);
            this.persist();
        },
        remove(key) {
            delete this.mix[key];
            this.persist();
        },
        clear() {
            this.mix = {};
            this.persist();
        },

        /* The mixer's contents follow the user across devices: save after each change. */
        persist() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(async () => {
                try {
                    await sendJson('PUT', data.endpoints.state, { mix: this.mix });
                } catch (e) {
                    this.error = 'Non sono riuscito a salvare il mixer: ricarica la pagina e riprova.';
                }
            }, 400);
        },

        async saveMix() {
            if (!Object.keys(this.mix).length) return;
            const name = this.name.trim() || 'Ricetta ' + color.mixHex(this.mix, palette);
            try {
                this.saved = (await sendJson('POST', data.endpoints.store, { name, mix: this.mix })).saved;
                this.name = '';
            } catch (e) {
                this.error = 'Non sono riuscito a salvare la ricetta: riprova.';
            }
        },
        openSaved(entry) {
            this.mix = { ...entry.mix };
            this.persist();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        async deleteSaved(entry) {
            try {
                this.saved = (await sendJson('DELETE', data.endpoints.destroy.replace('__ID__', entry.id))).saved;
            } catch (e) {
                this.error = 'Non sono riuscito a eliminare la ricetta: riprova.';
            }
        },
        savedStyle(entry) {
            return color.mixStyle(entry.mix, palette);
        },
        savedText(entry) {
            return color.ratioText(entry.mix, palette);
        },
    };
}

export function beginnerPath(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);
    const zone = { name: '', tab: '' };

    return {
        done: Object.fromEntries(data.done.map((id) => [id, true])),
        total: data.stepIds.length,
        error: null,

        get doneCount() {
            return data.stepIds.filter((id) => this.done[id]).length;
        },
        get pct() {
            return this.total ? Math.round((this.doneCount / this.total) * 100) : 0;
        },

        async toggle(id, checked) {
            if (checked) this.done[id] = true;
            else delete this.done[id];
            try {
                await sendJson('POST', data.toggleUrl.replace('__ID__', id), { done: checked });
            } catch (e) {
                if (checked) delete this.done[id];
                else this.done[id] = true;
                this.error = 'Non sono riuscito a salvare: ricarica la pagina e riprova.';
            }
        },

        /* "Ordine per dipingere una figura", with brush and sample mix. */
        order: data.order.map((step) => {
            const hasMix = Object.keys(step.mix).length > 0;
            const derived = withDerivedFields({ role: step.title, usage: step.description, mix: step.mix });
            return {
                title: step.title,
                description: step.description,
                brush: hasMix ? decorateStep(derived, zone, palette, data.brushes, data.urls).brush : null,
                ingredients: Object.keys(step.mix).map((key) => ingredient(key, step.mix[key], palette)),
            };
        }),
    };
}
