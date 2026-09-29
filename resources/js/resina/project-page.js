/*
 * A project page (renderProject() in the prototype): character cards
 * with their palette, armor types, step-by-step guides and the
 * project's recipes. The server renders one tab at a time; this
 * component only adds what needs the color math.
 */
import * as color from './color.js';
import { baseMixOf } from './steps.js';
import { charZones, zoneStyle } from './guide.js';
import { decorateRecipe, decorateStep, ingredient, readPayload, withDerivedFields } from './view.js';

export function projectPage(payloadId) {
    const data = readPayload(payloadId);
    const palette = color.createPalette(data.paints);
    const recipesById = data.recipes;
    const recipesBySlug = {};
    Object.values(recipesById).forEach((recipe) => {
        recipesBySlug[recipe.slug] = recipe;
    });

    const characters = data.characters.map((character) => {
        const version = character.versions.find((v) => v.id === data.chosenVersions[character.id]) || null;
        const zones = charZones(character, version, recipesBySlug, data.project.defaultBases);
        return {
            slug: character.slug,
            name: character.name,
            subtitle: character.subtitle,
            alias: character.alias_it,
            group: character.group,
            href: character.href,
            search: [character.name, character.alias_it, character.subtitle].join(' ').toLowerCase(),
            palette: zones
                .filter((zone) => !zone.auto && zone.tab !== 'basetta')
                .slice(0, 6)
                .map((zone) => {
                    const recipe = zone.recipeId ? recipesById[zone.recipeId] : null;
                    // Free-color zones show their color; no need to compute their recipe here.
                    return { key: zone.key, name: zone.name, style: zoneStyle(zone, recipe ? recipe.steps : [], palette) };
                }),
        };
    });

    return {
        groups: [{ slug: 'tutti', name: 'Tutti' }, ...data.groups],
        group: 'tutti',
        query: '',
        characters,

        visible(character) {
            const q = this.query.trim().toLowerCase();
            return (this.group === 'tutti' || character.group === this.group) && (!q || character.search.includes(q));
        },
        charactersOf(groupSlug) {
            return this.characters.filter((c) => c.group === groupSlug && this.visible(c));
        },
        get ungrouped() {
            const known = new Set(data.groups.map((g) => g.slug));
            return this.characters.filter((c) => !known.has(c.group) && this.visible(c));
        },
        get visibleCount() {
            return this.characters.filter((c) => this.visible(c)).length;
        },

        /* The swatch of a recipe: its base step. */
        recipeDot(recipeId) {
            const recipe = recipesById[recipeId];
            return recipe ? color.mixStyle(baseMixOf(recipe.steps), palette) : '';
        },

        guideSteps: Object.fromEntries(
            data.guides.map((guide) => {
                const zone = { name: '', tab: guide.slug === 'rock' ? 'basetta' : guide.slug === 'face' ? '' : 'armatura' };
                return [
                    guide.slug,
                    guide.steps.map((step) => {
                        const hasMix = Object.keys(step.mix || {}).length > 0;
                        const derived = withDerivedFields({ role: step.title, usage: step.description, mix: step.mix });
                        return {
                            title: step.title,
                            description: step.description,
                            brush: hasMix ? decorateStep(derived, zone, palette, data.brushes, data.urls).brush : null,
                            ingredients: Object.keys(step.mix || {})
                                .filter((key) => palette.byKey[key])
                                .map((key) => ingredient(key, step.mix[key], palette)),
                        };
                    }),
                ];
            }),
        ),

        recipes: data.projectRecipeIds
            .map((id) => recipesById[id])
            .filter(Boolean)
            .map((recipe) => decorateRecipe(recipe, palette, data.brushes, data.urls, data.editUrl)),
    };
}
