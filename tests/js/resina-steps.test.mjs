// Painting-mode logic of resources/js/resina/steps.js, on the real data
// files. Run with: npm run test:js
import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createPalette } from '../../resources/js/resina/color.js';
import { progressUnits, simpleTitle, stepGuide } from '../../resources/js/resina/steps.js';

const read = (file) => JSON.parse(fs.readFileSync(new URL('../../database/data/resina/' + file, import.meta.url), 'utf8'));
const data = read('technique_guides.json');
const recipes = read('recipes.json');
const kit = read('brushes.json').map((b, i) => ({ id: i, type: b.type, size: b.size, metallic_only: b.metallic_only }));
const palette = createPalette(read('paints.json').map((p) => ({ ...p, key: p.code })));

// The same shape ClientPayload::techniqueGuides() sends (TechniqueGuideImporter mapping).
const guides = Object.fromEntries(Object.entries(data.tecniche).map(([code, g]) => [code, { name: g.nome, preparation: g.preparazione, steps: g.come, wait_minutes: g.attesa_minuti, result: g.risultato, mistakes: g.errori }]));
const titles = data.titoli_semplici.map((t) => ({ pattern: t.ruolo, title: t.titolo }));
const texts = {
    metallico_preparazione: data.varianti.metallico.preparazione_extra,
    metallico_dopo: [data.varianti.metallico.dopo],
    shade_tmm: [data.varianti.shade_tmm.nota],
    light_metallico_drybrush: [data.varianti.light_metallico_drybrush.nota.replace('il Drybrush 3', '{pennello}')],
};
const ctx = { guides, texts, titles, palette, brushes: kit };
const recipe = (slug) => recipes.find((r) => r.slug === slug);
const asStep = (s) => ({ ...s, mix: Object.fromEntries(s.mix.map((m) => [m.paint, m.drops])) });

test('simple titles follow the first matching pattern, or the role itself', () => {
    const cases = {
        'Ombra (wash)': 'Scurisci gli incavi con il wash',
        Ombra: "Metti l'ombra nelle zone basse",
        'Ombra profonda': "Rinforza l'ombra nei punti più scuri",
        'Luce intermedia': 'Prima luce sulle superfici in alto',
        'Luce estrema': 'Luce sui punti più alti',
        Luce: 'Schiarisci i rilievi',
        'Punti luce': 'Puntini di luce',
        'Drybrush finale': 'Spazzola i rilievi (drybrush)',
        Base: 'Stendi il colore base',
        'Base scura (facoltativa)': 'Prima mano scura',
        'Mix rapido': 'Stendi il colore',
        'Ripresa della base': 'Ripulisci con il colore base',
        'Iride castana': 'Iride',
        Labbra: 'Labbra',
        '1. Argento': 'Argento',
    };
    for (const [role, title] of Object.entries(cases)) assert.equal(simpleTitle(role, titles), title, role);
});

test('cm-pearl, step by step: metallic preparation replaces the technique one', () => {
    const [mix, shade, light, points] = recipe('cm-pearl').steps.map(asStep).map((s) => stepGuide(s, ctx));

    assert.equal(mix.title, 'Stendi il colore');
    assert.equal(mix.metallic, true);
    assert.deepEqual(mix.preparation, data.varianti.metallico.preparazione_extra);
    assert.deepEqual(mix.how, data.tecniche.coprente.come);
    assert.equal(mix.waitMinutes, 20);
    assert.equal(mix.after, data.varianti.metallico.dopo);
    assert.equal(mix.lightAlternative, null);

    assert.equal(shade.title, "Metti l'ombra nelle zone basse");
    assert.equal(shade.technique, 'Wash (lavatura)');
    assert.equal(shade.waitMinutes, 45);
    assert.equal(shade.shadeNote, data.varianti.shade_tmm.nota);
    assert.equal(shade.metallic, false);

    assert.equal(light.title, 'Schiarisci i rilievi');
    assert.match(light.lightAlternative, /usando il Drybrush 3\.$/);

    assert.equal(points.title, 'Puntini di luce');
    assert.equal(points.metallic, false);
    assert.deepEqual(points.preparation, data.tecniche.punta.preparazione);
});

test('the drybrush alternative names the kit drybrush, or a generic one', () => {
    const light = asStep(recipe('cm-pearl').steps[2]);
    const noDrybrush = kit.filter((b) => b.type !== 'drybrush');
    const onlyBig = [...noDrybrush, { id: 99, type: 'drybrush', size: '9' }];

    assert.match(stepGuide(light, { ...ctx, brushes: noDrybrush }).lightAlternative, /usando un drybrush piccolo\.$/);
    assert.match(stepGuide(light, { ...ctx, brushes: onlyBig }).lightAlternative, /usando il Drybrush 9\.$/);
});

test('a step without a known technique still gets a title, with no instructions', () => {
    const guide = stepGuide({ role: 'Calore', technique: null, mix: {} }, ctx);
    assert.equal(guide.title, 'Calore');
    assert.deepEqual([guide.how, guide.preparation, guide.waitMinutes], [[], [], null]);
});

test('every catalog step now has a technique with instructions', () => {
    const missing = recipes.flatMap((r) => r.steps.filter((s) => !guides[s.technique]).map((s) => r.slug + ':' + s.role));
    assert.deepEqual(missing, []);
});

test('the iris colors count as one step, optional steps not at all', () => {
    const eyes = recipe('eyes').steps;
    assert.deepEqual(progressUnits(eyes), [[0], [1], [2, 3, 4, 5, 6], [7], [8]]);

    const face = recipe('face').steps;
    assert.equal(face[3].optional, true, 'Cicatrice is optional');
    assert.deepEqual(progressUnits(face), [[0], [1], [2]]);
});
