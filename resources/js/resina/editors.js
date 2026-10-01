/*
 * Admin editors: generic ordered rows (groups, links, armor recipes),
 * the zone editor and the guide editor. Rows move up and down; the
 * order on screen is the order saved.
 */
import * as color from './color.js';
import { readPayload, uid } from './view.js';

function moveIn(list, index, delta) {
    const target = index + delta;
    if (target < 0 || target >= list.length) return;
    const [item] = list.splice(index, 1);
    list.splice(target, 0, item);
}

/* Ordered rows of plain fields; blank is the shape of a new row. */
export function rowsEditor(payloadId, blank = {}) {
    return {
        rows: readPayload(payloadId).map((row) => ({ uid: uid(), ...row })),
        add() {
            this.rows.push({ uid: uid(), ...blank });
        },
        remove(index) {
            this.rows.splice(index, 1);
        },
        move(index, delta) {
            moveIn(this.rows, index, delta);
        },
    };
}

/*
 * Zones of a character or version: name, recipe or free color, an
 * optional reference color, the tab (or "automatica", from the name).
 */
export function zoneEditor(payloadId) {
    const data = readPayload(payloadId);

    return {
        rows: (data.rows || []).map((row) => ({
            uid: uid(),
            id: row.id ?? '',
            name: row.name ?? '',
            recipe_id: row.recipe_id ? String(row.recipe_id) : '',
            useTarget: !!row.target_hex || !row.recipe_id,
            target_hex: row.target_hex || '#8f9697',
            tab: row.tab ?? '',
            note: row.note ?? '',
        })),
        categories: data.categories,
        inline: data.inline || {},
        tabs: data.tabs,

        add() {
            this.rows.push({ uid: uid(), id: '', name: 'Nuova zona', recipe_id: '', useTarget: true, target_hex: '#8f9697', tab: '', note: '' });
        },
        remove(index) {
            if (confirm('Togliere questa zona?')) this.rows.splice(index, 1);
        },
        move(index, delta) {
            moveIn(this.rows, index, delta);
        },
        recipeChanged(row) {
            // No recipe: the zone lives on its color, which becomes required.
            if (!row.recipe_id) row.useTarget = true;
        },
        inlineFor(row) {
            return this.inline[row.recipe_id] || null;
        },
    };
}

/* Steps of a step-by-step guide: title, description, optional mix. */
export function guideEditor(payloadId) {
    const data = readPayload(payloadId);
    const catalog = data.paints.filter((p) => !p.custom);
    const palette = color.createPalette(catalog);
    const groups = [];
    catalog.forEach((p) => {
        let group = groups.find((g) => g.line === p.line);
        if (!group) groups.push((group = { line: p.line, paints: [] }));
        group.paints.push({ id: p.id, label: p.name + ' · ' + p.code });
    });

    return {
        steps: (data.steps || []).map((s) => ({
            uid: uid(),
            title: s.title ?? '',
            description: s.description ?? '',
            paints: (s.paints || []).map((p) => ({ paint_id: String(p.paint_id ?? ''), drops: Number(p.drops) || 1 })),
        })),
        paintGroups: groups,

        init() {
            if (!this.steps.length) this.addStep();
        },
        swatch(step) {
            const mix = {};
            step.paints.forEach((p) => {
                if (p.paint_id) mix['p' + p.paint_id] = (mix['p' + p.paint_id] || 0) + Number(p.drops || 0);
            });
            return color.mixStyle(mix, palette);
        },
        addStep() {
            this.steps.push({ uid: uid(), title: '', description: '', paints: [] });
        },
        removeStep(index) {
            this.steps.splice(index, 1);
        },
        moveStep(index, delta) {
            moveIn(this.steps, index, delta);
        },
        addPaint(step) {
            step.paints.push({ paint_id: '', drops: 1 });
        },
        removePaint(step, index) {
            step.paints.splice(index, 1);
        },
    };
}
