/*
 * The character sheet model, ported from the prototype (zoneTab,
 * CTABS, charZones, baseOf/zoneColor, tabStats).
 *
 * A zone here is { key, name, tab, recipeId, targetHex, note, auto,
 * versionId }. key identifies it for the "fatto" progress: "z{id}" for
 * stored zones, "auto:eyes", "auto:face" and "base:{slug}" for the ones
 * generated on the fly.
 */
import { mixStyle } from './color.js';
import { baseMixOf } from './steps.js';

/* Painting order. The armor tab takes its label from the project. */
export const TABS = [
    ['panoramica', 'Panoramica'],
    ['pelle', 'Pelle'],
    ['volto', 'Occhi e volto'],
    ['vestiti', 'Tuta e vestiti'],
    ['armatura', ''],
    ['capelli', 'Capelli'],
    ['dettagli', 'Dettagli'],
    ['basetta', 'Basetta'],
    ['reference', 'Reference'],
];

export const TAB_INTROS = {
    pelle: 'La pelle si dipinge per prima. Prima del colore dai una mano di Bianco Osso sopra il primer nero.',
    volto: 'Occhi subito dopo la base della pelle: se sbagli, ricopri con la pelle e riprovi. Labbra e guance alla fine, come velatura.',
    vestiti: "Tuta e vestiti prima dell'armatura: se sbordi, l'armatura copre l'errore.",
    armatura: "L'armatura dopo pelle e vestiti. Metti i metallici su un piattino, non sulla tavolozza bagnata.",
    capelli: "I capelli dopo l'armatura. Il drybrush sulle ciocche fa gran parte del lavoro.",
    dettagli: 'I dettagli si fanno quasi alla fine, con il pennello fine.',
    basetta: 'La basetta si dipinge per ultima. Qui trovi le ricette più adatte al personaggio: scegline una.',
};

/* Which tab a zone belongs to: its own, or deduced from the name. */
export function zoneTab(zone) {
    if (zone.tab) return zone.tab;
    const n = zone.name.toLowerCase();
    if (/pelle/.test(n)) return 'pelle';
    if (/capelli/.test(n)) return 'capelli';
    if (/occhi|neo|cicatrice|puntini|punto sulla fronte|labbra|sopracc/.test(n)) return 'volto';
    if (/tuta|mantello|vestito|pantaloni|parti bianche|abito/.test(n)) return 'vestiti';
    if (/cloth|maschera|scudo|catene|gemme|armatura|ali|armi|elmo|stella|surplice|scaglia|fibbia|oro/.test(n)) return 'armatura';
    return 'dettagli';
}

function storedZone(zone, versionId) {
    return { key: 'z' + zone.id, name: zone.name, tab: zone.tab, recipeId: zone.recipe_id, targetHex: zone.target_hex, note: zone.note, auto: false, versionId };
}

/*
 * The zones of a character with one version chosen: the version's zones
 * replace the shared ones with the same name and add to the rest; then
 * eyes and face (unless the figure has none) and the bases.
 *
 * character: { base_zones, no_face, no_eyes, bases }, version: { id, zones } | null,
 * recipesBySlug: { slug: recipe }, defaultBases: project bases or null.
 */
export function charZones(character, version, recipesBySlug, defaultBases) {
    const zones = character.base_zones.map((z) => storedZone(z, null));

    if (version) {
        version.zones.forEach((vz) => {
            const zone = storedZone(vz, version.id);
            let index = -1;
            zones.forEach((x, j) => {
                if (x.name === zone.name) index = j;
            });
            if (index >= 0) zones[index] = zone;
            else zones.push(zone);
        });
    }

    const hasFace = !character.no_face;
    if (hasFace && !character.no_eyes && !zones.some((x) => /^occhi/i.test(x.name)) && recipesBySlug.eyes) {
        zones.push({ key: 'auto:eyes', name: 'Occhi', tab: 'volto', recipeId: recipesBySlug.eyes.id, targetHex: null, note: null, auto: false, versionId: null });
    }
    if (hasFace && recipesBySlug.face) {
        zones.push({ key: 'auto:face', name: 'Labbra, guance e sopracciglia', tab: 'volto', recipeId: recipesBySlug.face.id, targetHex: null, note: null, auto: true, versionId: null });
    }

    const bases = character.bases && character.bases.length ? character.bases : defaultBases && defaultBases.length ? defaultBases : ['base-rock', 'base-earth'];
    bases.forEach((slug) => {
        const recipe = recipesBySlug[slug];
        if (recipe) {
            zones.push({ key: 'base:' + slug, name: recipe.title, tab: 'basetta', recipeId: recipe.id, targetHex: null, note: null, auto: true, versionId: null });
        }
    });

    return zones;
}

/*
 * A zone's swatch: its free color when it has no recipe, otherwise the
 * color of its recipe's base step (zoneColor() in the prototype).
 */
export function zoneStyle(zone, steps, palette) {
    if (zone.targetHex && !zone.recipeId) return 'background:' + zone.targetHex;
    if (!steps.length) return 'background:var(--resina-chip, #e5e7eb)';
    return mixStyle(baseMixOf(steps), palette);
}

/*
 * The visible tabs, in painting order: Panoramica and Reference always,
 * the others only when they have zones.
 */
export function visibleTabs(zones, armorLabel) {
    const used = new Set(zones.map(zoneTab));
    return TABS.filter(([key]) => key === 'panoramica' || key === 'reference' || used.has(key)).map(([key, label]) => ({
        key,
        label: label || armorLabel || 'Armatura',
    }));
}

/* Image-search links for the Reference tab (linksHTML() in the prototype). */
export function referenceLinks(query) {
    const e = encodeURIComponent;
    return [
        ["Immagini dell'anime", 'https://www.google.com/search?tbm=isch&q=' + e(query + ' anime'), 'Colori originali della serie.'],
        ['Myth Cloth e figure', 'https://www.google.com/search?tbm=isch&q=' + e(query + ' Myth Cloth EX'), 'Le action figure ufficiali.'],
        ['Statue dipinte', 'https://www.google.com/search?tbm=isch&q=' + e(query + ' statue painted'), 'Come altri hanno dipinto statue simili.'],
        ['Pinterest', 'https://www.pinterest.com/search/pins/?q=' + e(query + ' painted figure'), 'Raccolte di reference.'],
        ['Video di pittura', 'https://www.youtube.com/results?search_query=' + e(query + ' painting figure'), 'Tutorial su YouTube.'],
    ].map(([title, url, description]) => ({ title, url, description }));
}

export function versionReferenceLink(query, version) {
    return 'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(query + ' ' + version.label.replace('Anime ', 'anime ') + ' cloth');
}
