<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrections to the catalog steps for the painting mode (version 2),
     * the same ones made in database/data/resina/*.json so "Reimporta
     * catalogo" keeps them:
     *  - choice_group: the five iris colors of the "eyes" recipe are
     *    alternatives ("iride"), painted and counted as one step;
     *  - a technique for every step that had none, and the three
     *    Ombra made only of Shade TMM become "wash";
     *  - "Dove" texts that said what color, not where it goes;
     *  - the Cicatrice of the "face" recipe is optional.
     *
     * Steps are found by recipe slug and position, which never change
     * on import. Saved progress ("zone|position") is untouched.
     */
    public function up(): void
    {
        Schema::table('resin_recipe_steps', function (Blueprint $table) {
            $table->string('choice_group', 50)->nullable()->after('coverage');
        });

        $recipeIds = DB::table('resin_recipes')->pluck('id', 'slug');

        foreach (self::CHANGES as [$slug, $position, $values]) {
            if (! isset($recipeIds[$slug])) {
                continue;
            }

            DB::table('resin_recipe_steps')
                ->where('recipe_id', $recipeIds[$slug])
                ->where('position', $position)
                ->update($values);
        }
    }

    /**
     * Only the column goes: the corrected texts and techniques stay.
     */
    public function down(): void
    {
        Schema::table('resin_recipe_steps', function (Blueprint $table) {
            $table->dropColumn('choice_group');
        });
    }

    /**
     * [recipe slug, step position, new values]
     *
     * @var array<int, array{0: string, 1: int, 2: array<string, mixed>}>
     */
    private const CHANGES = [
        ['face', 2, ['technique' => 'velatura']],
        ['face', 3, ['usage' => 'Sopra gli occhi: una linea sottile per sopracciglio. Per capelli colorati usa il colore dei capelli.']],
        ['face', 4, ['optional' => true, 'usage' => 'Solo se il personaggio ha una cicatrice: una linea sottile dove c\'è, poi una luce di Carne Elfica su un solo lato.']],
        ['eyes', 2, ['technique' => 'punta', 'usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['eyes', 3, ['technique' => 'punta', 'choice_group' => 'iride', 'usage' => 'Un cerchio che tocca la palpebra superiore. Per occhi azzurri.']],
        ['eyes', 4, ['technique' => 'punta', 'choice_group' => 'iride', 'usage' => 'Un cerchio che tocca la palpebra superiore. Per occhi blu intensi.']],
        ['eyes', 5, ['technique' => 'punta', 'choice_group' => 'iride', 'usage' => 'Un cerchio che tocca la palpebra superiore. Per occhi castani.']],
        ['eyes', 6, ['technique' => 'punta', 'choice_group' => 'iride', 'usage' => 'Un cerchio che tocca la palpebra superiore. Per occhi verdi.']],
        ['eyes', 7, ['technique' => 'punta', 'choice_group' => 'iride', 'usage' => 'Un cerchio che tocca la palpebra superiore. Per occhi rossi (personaggi malvagi).']],
        ['eyes', 8, ['usage' => 'Un punto al centro dell\'iride.']],
        ['metal-silver', 5, ['usage' => 'Micro punti sugli spigoli più esposti.']],
        ['metal-goldanime', 1, ['usage' => 'Su tutta la zona: è un oro più giallo.']],
        ['metal-goldanime', 2, ['usage' => 'Su tutto l\'oro: 1 goccia + 5 d\'acqua, più passate.']],
        ['metal-oldgold', 1, ['usage' => 'Su tutta la zona: oro scurito con il suo Shade.']],
        ['metal-oldgold', 2, ['technique' => 'coprente', 'usage' => 'Al posto del Mix, su tutta la zona, se lo vuoi più marrone.']],
        ['metal-surplice', 2, ['usage' => 'Su tutta la zona: 1 goccia + 4 d\'acqua, più passate.']],
        ['metal-surplice', 4, ['usage' => 'Sui bordi delle placche, una linea sottilissima.']],
        ['metal-black', 3, ['usage' => 'Sui bordi delle placche, una linea sottile.']],
        ['cm-method', 1, ['technique' => 'coprente']],
        ['cm-method', 2, ['usage' => 'Su tutta la zona argentata: il colore che vuoi, 1 goccia + 4–5 d\'acqua, più passate.']],
        ['cm-red', 1, ['usage' => 'Su tutta la zona: rosso metallico.']],
        ['cm-green', 1, ['usage' => 'Su tutta la zona: verde metallico.']],
        ['cm-pink', 1, ['usage' => 'Su tutta la zona: rosa metallico.']],
        ['cm-blue', 1, ['usage' => 'Su tutta la zona: blu metallico scuro.']],
        ['cm-icy', 1, ['usage' => 'Su tutta la zona: argento freddo.']],
        ['cm-purple', 1, ['usage' => 'Su tutta la zona: viola metallico scuro.']],
        ['cm-orange', 1, ['usage' => 'Su tutta la zona: arancio metallico.']],
        ['cm-burgundy', 1, ['usage' => 'Su tutta la zona: bordeaux metallico.']],
        ['cm-darkgreen', 1, ['usage' => 'Su tutta la zona: verde scurissimo metallico.']],
        ['cm-ironblue', 1, ['usage' => 'Su tutta la zona: argento blu ferro.']],
        ['cm-pearl', 1, ['usage' => 'Su tutta la zona: bianco perlato.']],
        ['cm-blue', 3, ['usage' => 'Sui bordi delle placche, una linea sottile.']],
        ['cm-burgundy', 4, ['usage' => 'Sui bordi delle placche, una linea sottile.']],
        ['cm-darkgreen', 3, ['usage' => 'Sui bordi delle placche, una linea sottile.']],
        ['cm-pearl', 2, ['technique' => 'wash', 'usage' => 'Negli incavi.']],
        ['cm-icy', 2, ['technique' => 'wash']],
        ['cm-orange', 2, ['technique' => 'wash', 'usage' => 'Negli incavi.']],
        ['fab-white', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-red', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-blue', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-lightblue', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-green', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-darkgreen', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-pink', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-lavender', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-lightpink', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-purple', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-black', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-yellow', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-orange', 1, ['usage' => 'Su tutto il tessuto.']],
        ['fab-leather', 1, ['usage' => 'Su tutto il cuoio.']],
        ['rose', 2, ['technique' => 'sottile']],
        ['rose', 3, ['technique' => 'spigoli', 'usage' => 'Solo i bordi dei petali.']],
        ['rose', 4, ['technique' => 'coprente']],
        ['wood', 1, ['usage' => 'Su tutto il legno.']],
        ['wood', 2, ['technique' => 'punta', 'usage' => 'Linee sottili lungo il legno, seguendo le venature.']],
        ['fx-cosmo', 1, ['usage' => 'Sull\'aura attorno alla figura: velatura calda.']],
        ['fx-cosmo', 2, ['usage' => 'Sull\'aura attorno alla figura: velatura fredda.']],
        ['fx-cosmo', 3, ['technique' => 'velatura', 'usage' => 'Sull\'aura attorno alla figura: per Hades e le energie oscure.']],
        ['fx-cosmo', 4, ['technique' => 'velatura', 'usage' => 'Sull\'aura attorno alla figura: per Scorpione e la rabbia.']],
        ['fx-fire', 1, ['technique' => 'coprente']],
        ['fx-fire', 2, ['technique' => 'sottile']],
        ['fx-fire', 3, ['technique' => 'sottile']],
        ['fx-fire', 4, ['technique' => 'sottile']],
        ['fx-ice', 3, ['technique' => 'spigoli']],
        ['fx-tattoo', 1, ['usage' => 'Sul disegno del tatuaggio, molto diluito.']],
        ['fx-tattoo', 2, ['usage' => 'Dentro il contorno del tatuaggio, in velatura.']],
        ['base-earth', 1, ['usage' => 'Su tutta la terra, come fondo scuro (o un wash).']],
        ['base-earth', 3, ['usage' => 'Su tutta la terra, sui rilievi.']],
        ['base-earth', 4, ['usage' => 'Solo sui rilievi più alti, leggerissimo.']],
        ['base-sand', 2, ['usage' => 'Su tutta la sabbia.']],
        ['base-sand', 3, ['usage' => 'Sui rilievi della sabbia, a drybrush.']],
        ['base-rock', 2, ['usage' => 'In tutte le fessure della roccia: lo Shade dell\'argento funziona anche qui.']],
        ['base-rock', 3, ['usage' => 'Su tutta la roccia, sui rilievi.']],
        ['base-warmrock', 1, ['usage' => 'Su tutta la pietra.']],
        ['base-marble', 2, ['usage' => 'Su tutto il marmo.']],
        ['base-marble', 3, ['technique' => 'punta', 'usage' => 'Linee sottili e spezzate sul marmo, molto diluite.']],
        ['base-grass', 1, ['usage' => 'Su tutta l\'erba.']],
        ['base-grass', 2, ['usage' => 'Sui fili d\'erba, a drybrush.']],
        ['base-grass', 3, ['usage' => 'Sulle punte dell\'erba, drybrush leggero.']],
        ['base-grass', 4, ['technique' => 'drybrush', 'usage' => 'Qua e là sull\'erba, per variare.']],
        ['base-snow', 2, ['usage' => 'Su tutta la neve.']],
        ['base-lava', 1, ['technique' => 'coprente', 'usage' => 'Su tutta la roccia.']],
        ['base-lava', 2, ['technique' => 'punta']],
        ['base-lava', 3, ['technique' => 'punta']],
        ['base-lava', 4, ['technique' => 'punta']],
        ['inline-saint-seiya-seiya-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-seiya-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-seiya-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore.']],
        ['inline-saint-seiya-seiya-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-shiryu-base-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-shiryu-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-shiryu-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-shiryu-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: blu scurissimo, quasi nero.']],
        ['inline-saint-seiya-shiryu-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-hyoga-base-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-hyoga-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-hyoga-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-hyoga-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: azzurri.']],
        ['inline-saint-seiya-hyoga-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-shun-base-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-shun-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-shun-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-shun-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: verdi (anime).']],
        ['inline-saint-seiya-shun-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-shun-m-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-shun-m-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-shun-m-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-shun-m-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: azzurri (manga).']],
        ['inline-saint-seiya-shun-m-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-ikki-base-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-ikki-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-ikki-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-ikki-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: blu scuri.']],
        ['inline-saint-seiya-ikki-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-shaina-base-3', 1, ['usage' => 'Su tutta la maschera, due mani sottilissime.']],
        ['inline-saint-seiya-shaina-base-3', 2, ['usage' => 'Fronte e zigomi della maschera.']],
        ['inline-saint-seiya-saga-base-4', 1, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: rossi.']],
        ['inline-saint-seiya-saga-base-4', 2, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-deathmask-base-3', 1, ['usage' => 'Linea superiore e fessura dell\'occhio.']],
        ['inline-saint-seiya-deathmask-base-3', 2, ['usage' => 'Dentro il contorno, ai lati dell\'iride. Mai bianco puro.']],
        ['inline-saint-seiya-deathmask-base-3', 2, ['technique' => 'punta']],
        ['inline-saint-seiya-deathmask-base-3', 3, ['technique' => 'punta', 'usage' => 'Un cerchio che tocca la palpebra superiore: blu.']],
        ['inline-saint-seiya-deathmask-base-3', 4, ['usage' => 'Un punto al centro dell\'iride.']],
        ['inline-saint-seiya-milo-base-3', 1, ['usage' => 'Su tutta l\'unghia.']],
        ['inline-saint-seiya-milo-base-3', 2, ['usage' => 'Sulla parte alta dell\'unghia.']],
        ['inline-saint-seiya-milo-base-3', 3, ['usage' => 'Un punto sulla punta dell\'unghia.']],
        ['inline-marvel-spiderman-base-3', 1, ['technique' => 'punta', 'usage' => 'Sul costume, seguendo il disegno della ragnatela: linee sottilissime, colore ben diluito. Con calma.']],
        ['inline-marvel-spiderman-base-4', 1, ['technique' => 'coprente', 'usage' => 'Dentro le lenti, su tutta la superficie.']],
        ['inline-marvel-spiderman-base-4', 2, ['usage' => 'Il bordo nero attorno alle lenti.']],
    ];
};
