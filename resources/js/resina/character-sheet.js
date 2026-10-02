/*
 * The character sheet (buildGuide() in the prototype): version picker,
 * big palette, tabs in painting order with done/total counters, zones
 * with their steps and "fatto" checkboxes saved as you tick them.
 * Used for catalog characters and, later, personal figures.
 */
import * as color from './color.js';
import * as brushes from './brushes.js';
import { baseMixOf } from './steps.js';
import { charZones, referenceLinks, TAB_INTROS, visibleTabs, zoneStyle, zoneTab } from './guide.js';
import { runMixJobs } from './mix-jobs.js';
import { decorateStep, ingredient, paintStyle, readPayload, sendJson, toneScale, withDerivedFields } from './view.js';

/* Free-color zones get Ombra / Base / Luce: always three steps. */
const AUTO_STEP_COUNT = 3;

export function characterSheet(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);
    const owned = data.paints.filter((p) => p.owned);
    const character = data.character;
    const recipesById = data.recipes;
    const recipesBySlug = {};
    Object.values(recipesById).forEach((recipe) => {
        recipesBySlug[recipe.slug] = recipe;
    });
    const armorLabel = data.project.armorLabel;

    const zonesByVersion = new Map();
    const viewCache = new Map();

    function zonesFor(versionId) {
        if (!zonesByVersion.has(versionId)) {
            const version = character.versions.find((v) => v.id === versionId) || null;
            zonesByVersion.set(versionId, charZones(character, version, recipesBySlug, data.project.defaultBases));
        }
        return zonesByVersion.get(versionId);
    }

    return {
        versionId: data.chosenVersionId,
        tab: data.tab,
        done: Object.fromEntries(data.done.map((key) => [key, true])),
        autoSteps: {},
        error: null,

        character,
        versions: character.versions,
        urls: data.urls,
        copyUrl: data.copyUrl,
        canUploadReferences: !!data.canUploadReferences,
        referenceLinks: referenceLinks(character.search_query),

        init() {
            this.computeAutoZones();
            // A reference photo loaded from this page shows up at once.
            window.addEventListener('resina-reference-saved', (event) => {
                const version = this.versions.find((v) => v.id === event.detail.id);
                if (version) Object.assign(version, { photo: event.detail.photo, source: event.detail.source });
            });
            this.$nextTick(() => this.revealActive());
        },

        /* On phones the chip rows scroll sideways: keep the active tab and version in sight. */
        revealActive() {
            [this.$refs.tabStrip, this.$refs.versionStrip].forEach((strip) => {
                const active = strip?.querySelector('[data-active="true"]');
                if (active) strip.scrollLeft = active.offsetLeft - (strip.clientWidth - active.clientWidth) / 2;
            });
        },

        get version() {
            return this.versions.find((v) => v.id === this.versionId) || null;
        },
        get zones() {
            return zonesFor(this.versionId);
        },
        get tabs() {
            return visibleTabs(this.zones, armorLabel);
        },
        get paintingTabs() {
            return this.tabs.filter((t) => t.key !== 'panoramica' && t.key !== 'reference');
        },
        get currentTab() {
            return this.tabs.find((t) => t.key === this.tab) || this.tabs[0];
        },
        get nextTab() {
            const index = this.tabs.findIndex((t) => t.key === this.currentTab.key);
            const next = this.tabs[index + 1];
            return next && next.key !== 'reference' ? next : null;
        },
        get armorLabelLower() {
            return (armorLabel || 'armatura').toLowerCase();
        },

        setTab(key) {
            this.tab = key;
            history.replaceState(null, '', data.baseUrl + '/' + key);
            // Already past the tab bar: jump back to the start of the new tab.
            this.$nextTick(() => {
                this.revealActive();
                const anchor = this.$refs.tabsAnchor;
                if (!anchor) return;
                const top = anchor.getBoundingClientRect().top + window.scrollY - 72;
                if (window.scrollY > top) window.scrollTo({ top });
            });
        },

        /* The steps a zone is painted with, or null while computing. */
        stepsOf(zone) {
            if (zone.recipeId && recipesById[zone.recipeId]) return recipesById[zone.recipeId].steps;
            if (zone.targetHex) return this.autoSteps[zone.targetHex] || null;
            return [];
        },

        /* Free-color zones: their recipe comes from the user's own paints, in the worker. */
        computeAutoZones() {
            const pending = new Set();
            character.versions.map((v) => v.id).concat([null]).forEach((versionId) => {
                zonesFor(versionId).forEach((zone) => {
                    if (!zone.recipeId && zone.targetHex && !this.autoSteps[zone.targetHex]) pending.add(zone.targetHex);
                });
            });
            runMixJobs(owned, [...pending].map((hex) => ({ id: hex, kind: 'auto', hex })), (hex, result) => {
                this.autoSteps[hex] = result.map(withDerivedFields);
            });
        },

        zonesOf(tabKey) {
            return this.zones.filter((zone) => zoneTab(zone) === tabKey);
        },

        doneKey(zone, index) {
            return zone.key + '|' + (index + 1);
        },

        /* [done, total] of a tab, optional steps excluded. */
        stats(tabKey) {
            let total = 0, done = 0;
            this.zonesOf(tabKey).forEach((zone) => {
                const steps = this.stepsOf(zone);
                if (!steps) {
                    total += AUTO_STEP_COUNT;
                    for (let i = 0; i < AUTO_STEP_COUNT; i++) if (this.done[this.doneKey(zone, i)]) done++;
                    return;
                }
                steps.forEach((step, i) => {
                    if (step.optional) return;
                    total++;
                    if (this.done[this.doneKey(zone, i)]) done++;
                });
            });
            return [done, total];
        },

        /* Overall progress: every painting tab but the base. */
        get overall() {
            let done = 0, total = 0;
            this.paintingTabs.forEach((t) => {
                if (t.key === 'basetta') return;
                const [d, n] = this.stats(t.key);
                done += d;
                total += n;
            });
            return { done, total, pct: total ? Math.round((done / total) * 100) : 0 };
        },

        /* Everything a zone card shows. */
        zoneView(zone) {
            const steps = this.stepsOf(zone);
            const cacheKey = zone.key + (steps ? '' : ':pending');
            if (viewCache.has(cacheKey)) return viewCache.get(cacheKey);

            const recipe = zone.recipeId ? recipesById[zone.recipeId] : null;
            const brushZone = { name: zone.name, tab: zone.tab };
            const decorated = (steps || []).map((step) => decorateStep(step, brushZone, palette, data.brushes, data.urls));
            const view = {
                key: zone.key,
                name: zone.name,
                pending: !steps,
                swatch: zoneStyle(zone, steps || [], palette),
                recipeLink: recipe && !recipe.is_inline ? { title: recipe.title, href: data.urls.recipes ? data.urls.recipes + '#r-' + recipe.slug : null } : null,
                computed: !recipe && !!zone.targetHex,
                note: zone.note,
                tip: recipe && recipe.tip ? recipe.tip : null,
                steps: decorated,
                scale: decorated.length > 1 ? toneScale(decorated) : [],
                info: this.stepsInfo(steps || []),
                target: steps ? this.targetInfo(zone, steps) : null,
            };
            if (steps) viewCache.set(cacheKey, view);
            return view;
        },

        stepsInfo(steps) {
            if (steps.length <= 1) return null;
            const optional = steps.filter((s) => s.optional).length;
            return steps.length + " passaggi, nell'ordine in cui si dipingono" + (optional ? '. Quelli segnati «facoltativo» (' + optional + ') puoi saltarli nelle prime figure' : '') + '.';
        },

        /* Reference color vs the recipe's base, and what to buy when far. */
        targetInfo(zone, steps) {
            if (!zone.targetHex) return null;
            const baseMix = steps.length ? baseMixOf(steps) : null;
            const lin = baseMix ? color.mixLin(baseMix, palette) : null;
            const d = lin ? color.dE(color.lab(color.hexLin(zone.targetHex)), color.lab(lin)) : 99;
            let buy = null;
            if (d >= 10) {
                const nearest = color.nearestBuy(zone.targetHex, data.suggestions);
                if (nearest && !nearest.b.owned) buy = nearest.b;
            }
            return { hex: zone.targetHex.toUpperCase(), closeness: color.closeness(d), buy };
        },

        get bigPalette() {
            return this.zones
                .filter((zone) => zone.tab !== 'basetta' && !zone.auto)
                .map((zone) => {
                    const style = zoneStyle(zone, this.stepsOf(zone) || [], palette);
                    const m = /background:(#[0-9A-Fa-f]{6})$/.exec(style);
                    const ink = m && color.lab(color.hexLin(m[1]))[0] < 60 ? '#fff' : '#1B1F2A';
                    return { key: zone.key, name: zone.name, style: style + ';color:' + ink };
                });
        },

        get workOrder() {
            return this.paintingTabs.map((t) => {
                const [done, total] = this.stats(t.key);
                return { key: t.key, label: t.label, zones: this.zonesOf(t.key).map((z) => z.name).join(' · '), done, total };
            });
        },

        /* Paints used outside the base, in shelf order, with codes. */
        get paintsUsed() {
            const used = {};
            this.zones.forEach((zone) => {
                if (zone.tab === 'basetta') return;
                (this.stepsOf(zone) || []).forEach((step) => {
                    Object.keys(step.mix || {}).forEach((key) => {
                        if (palette.byKey[key]) used[key] = true;
                    });
                });
            });
            return palette.list.filter((p) => used[p.key]).map((p) => ({ key: p.key, name: p.name, code: p.code, style: paintStyle(p) }));
        },

        /* Brushes the sheet asks for, with the zones each one is for. */
        get brushesUsed() {
            const groups = {};
            this.zones.forEach((zone) => {
                (this.stepsOf(zone) || []).forEach((step) => {
                    const bf = brushes.brushFor(step, { name: zone.name, tab: zone.tab }, data.brushes);
                    const key = bf.b ? 'b' + bf.b.id : bf.slot;
                    if (!groups[key]) groups[key] = { key, brush: bf.b, slot: bf.slot, zones: {} };
                    groups[key].zones[zone.name] = true;
                });
            });
            const sortKey = (g) => (g.brush ? g.brush.type + brushes.bval(g.brush.size) : 'z');
            return Object.values(groups)
                .sort((a, b) => sortKey(a).localeCompare(sortKey(b)))
                .map((g) => ({
                    key: g.key,
                    label: g.brush ? brushes.brushLabel(g.brush) : brushes.SLOTS[g.slot].n,
                    missing: !g.brush,
                    description: brushes.SLOTS[g.slot].d,
                    zones: Object.keys(g.zones).join(' · '),
                }));
        },

        get versionLinks() {
            return this.versions.map((v) => ({ label: v.label, subtitle: v.subtitle, url: v.searchUrl }));
        },

        intro(tabKey) {
            return TAB_INTROS[tabKey] || '';
        },

        async chooseVersion(versionId) {
            if (versionId === this.versionId) return;
            this.versionId = versionId;
            this.$nextTick(() => this.revealActive());
            try {
                await sendJson('POST', data.endpoints.version, { version_id: versionId });
            } catch (e) {
                this.error = 'Non sono riuscito a salvare la versione: ricarica la pagina e riprova.';
            }
            if (!this.tabs.some((t) => t.key === this.tab)) this.setTab('panoramica');
        },

        async toggle(zone, index, checked) {
            const key = this.doneKey(zone, index);
            if (checked) this.done[key] = true;
            else delete this.done[key];
            try {
                await sendJson('POST', data.endpoints.progress, { zone_key: zone.key, step_position: index + 1, done: checked });
            } catch (e) {
                if (checked) delete this.done[key];
                else this.done[key] = true;
                this.error = 'Non sono riuscito a salvare: ricarica la pagina e riprova.';
            }
        },

        async resetDone() {
            if (!confirm('Azzerare tutti i passaggi segnati come fatti per questo personaggio?')) return;
            const previous = this.done;
            this.done = {};
            try {
                await sendJson('DELETE', data.endpoints.reset);
            } catch (e) {
                this.done = previous;
                this.error = 'Non sono riuscito ad azzerare: ricarica la pagina e riprova.';
            }
        },

        ingredientsOf(mix) {
            return Object.keys(mix || {})
                .filter((key) => palette.byKey[key])
                .map((key) => ingredient(key, mix[key], palette));
        },
    };
}
