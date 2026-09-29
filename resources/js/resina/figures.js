/*
 * "Le mie figure" list and "Analizza una foto" (renderFigure,
 * renderAnalizza and makeGuide in the prototype; the guide itself is
 * built on the server).
 */
import * as color from './color.js';
import { zoneStyle } from './guide.js';
import { runMixJobs } from './mix-jobs.js';
import { canvasToJpeg, dominantColors, loadImageFile, sampleAt, scaledCanvas } from './photo.js';
import { ingredient, readPayload } from './view.js';

/* The first six zones of each figure, as color strips on its card. */
export function figureList(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);

    return {
        palettes: Object.fromEntries(
            data.figures.map((figure) => [
                figure.id,
                figure.zones.map((zone) => {
                    const recipe = zone.recipe_id ? data.recipes[zone.recipe_id] : null;
                    const z = { key: 'z' + zone.id, name: zone.name, recipeId: zone.recipe_id, targetHex: zone.target_hex };
                    return { key: z.key, name: z.name, style: zoneStyle(z, recipe ? recipe.steps : [], palette) };
                }),
            ]),
        ),
    };
}

/* Tabs a photo color can be assigned to (ZCATS in the prototype). */
const ZONE_TABS = [
    ['', '— ignora —'],
    ['pelle', 'Pelle'],
    ['volto', 'Occhi e volto'],
    ['capelli', 'Capelli'],
    ['vestiti', 'Tuta e vestiti'],
    ['armatura', 'Armatura / cloth'],
    ['dettagli', 'Dettagli'],
    ['basetta', 'Basetta'],
];

/* Upload size: longest side of the photo and of its thumbnail. */
const PHOTO_MAX = 1600;
const THUMB_MAX = 320;
/* Canvas sizes of the prototype: 900 px to tap on, 220 px to analyse. */
const VIEW_MAX = 900;
const ANALYSIS_MAX = 220;

const LOAD_ERRORS = {
    heic: "Questa è una foto HEIC dell'iPhone e questo browser non riesce ad aprirla. Dall'iPhone caricala con Safari (la converte da solo), oppure salvala prima come JPEG.",
    'not-image': "Il file non è un'immagine.",
    unreadable: "Non riesco ad aprire questa immagine: prova con un JPG o un PNG.",
};

export function photoAnalysis(payloadId) {
    const data = readPayload(payloadId);
    const owned = data.paints.filter((p) => p.owned);
    const inventory = color.createPalette(owned);
    const characters = data.projects.flatMap((project) => project.characters);

    let photoBlob = null;
    let thumbBlob = null;
    let nextId = 0;

    return {
        projects: data.projects,
        zoneTabs: ZONE_TABS,
        loaded: false,
        loading: false,
        dragging: false,
        characterId: '',
        versionId: '',
        name: '',
        series: '',
        notes: '',
        colors: [],
        message: '',
        saving: false,

        get character() {
            return characters.find((c) => String(c.id) === String(this.characterId)) || null;
        },
        characterChanged() {
            this.versionId = this.character && this.character.versions.length ? String(this.character.versions[0].id) : '';
        },

        async pick(file) {
            if (!file) return;
            this.message = '';
            this.loading = true;
            try {
                const img = await loadImageFile(file);
                const photo = scaledCanvas(img, PHOTO_MAX);
                photoBlob = await canvasToJpeg(photo, 0.85);
                thumbBlob = await canvasToJpeg(scaledCanvas(img, THUMB_MAX), 0.75);

                const view = scaledCanvas(img, VIEW_MAX);
                const target = this.$refs.canvas;
                target.width = view.width;
                target.height = view.height;
                target.getContext('2d').drawImage(view, 0, 0);

                this.colors = dominantColors(scaledCanvas(img, ANALYSIS_MAX), 8).map((c) => this.newColor(c.hex, c.pct, false));
                this.computeMixes(this.colors);
                this.loaded = true;
            } catch (e) {
                this.message = LOAD_ERRORS[e.message] || LOAD_ERRORS.unreadable;
            } finally {
                this.loading = false;
            }
        },
        dropped(event) {
            this.dragging = false;
            this.pick(event.dataTransfer.files[0]);
        },

        /* Tap on the photo: that color goes to the top of the list. */
        sample(event) {
            const canvas = event.currentTarget, r = canvas.getBoundingClientRect();
            const x = Math.round(((event.clientX - r.left) * canvas.width) / r.width), y = Math.round(((event.clientY - r.top) * canvas.height) / r.height);
            const entry = this.newColor(sampleAt(canvas, x, y), 0, true);
            this.colors.unshift(entry);
            this.computeMixes([entry]);
        },

        newColor(hex, pct, picked) {
            return { id: ++nextId, hex, pct, picked, tab: '', name: '', best: null };
        },

        /* The closest mix of your own paints for each color, in one worker run. */
        computeMixes(entries) {
            runMixJobs(owned, entries.map((entry) => ({ id: entry.id, kind: 'find', hex: entry.hex })), (id, list) => {
                const target = this.colors.find((c) => c.id === id);
                const best = list[0];
                if (!target || !best) return;
                target.best = {
                    closeness: color.closeness(best.d),
                    ingredients: Object.keys(best.mix).map((key) => ingredient(key, best.mix[key], inventory)),
                };
            });
        },
        removeColor(index) {
            this.colors.splice(index, 1);
        },

        async createGuide() {
            if (!photoBlob) {
                this.message = "Carica prima un'immagine.";
                return;
            }
            const assigned = this.colors.filter((c) => c.tab);
            if (!assigned.length && !this.characterId) {
                this.message = 'Assegna almeno un colore a una zona, oppure scegli un personaggio.';
                return;
            }

            const form = new FormData();
            form.append('photo', photoBlob, 'foto.jpg');
            form.append('thumb', thumbBlob, 'miniatura.jpg');
            if (this.characterId) {
                form.append('character_id', this.characterId);
                if (this.versionId) form.append('version_id', this.versionId);
            } else {
                form.append('name', this.name);
                form.append('series', this.series);
                form.append('notes', this.notes);
            }
            assigned.forEach((c, i) => {
                form.append(`colors[${i}][hex]`, c.hex);
                form.append(`colors[${i}][tab]`, c.tab);
                form.append(`colors[${i}][name]`, c.name);
            });

            this.saving = true;
            this.message = '';
            try {
                const response = await fetch(data.storeUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                    body: form,
                });
                const body = await response.json().catch(() => ({}));
                if (response.ok && body.redirect) {
                    window.location = body.redirect;
                    return;
                }
                const first = body.errors ? Object.values(body.errors)[0][0] : null;
                this.message = first || body.message || 'Non sono riuscito a creare la guida: riprova.';
            } catch (e) {
                this.message = 'Non sono riuscito a inviare la foto: controlla la connessione e riprova.';
            } finally {
                this.saving = false;
            }
        },
    };
}
