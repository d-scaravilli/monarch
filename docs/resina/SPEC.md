# Modulo "3D - Resina" per Monarch — Specifica

Questo documento descrive il nuovo modulo **3D - Resina** di Monarch. Il modulo aiuta un principiante assoluto a dipingere a pennello stampe 3D in resina, per personaggi di diversi progetti: Saint Seiya, Power Rangers, Marvel e figure personali.

## Materiale di riferimento in questa cartella

- `reference.html` — prototipo completo e funzionante (un solo file, HTML e JavaScript). È il riferimento **funzionale e di UX**: ogni schermata, testo, regola e calcolo del modulo deve comportarsi come qui. Aprilo in un browser e leggi il codice JavaScript in fondo al file.
- `database/data/resina/*.json` (nella root del progetto, non in questa cartella) — tutti i contenuti già estratti dal prototipo. È l'unica copia dei dati: la legge l'importer (`App\Services\Resina\CatalogImporter`, richiamato dalla migration dati e da `php artisan resina:importa`). Sono la **fonte dei dati iniziali**: colori, pennelli, ricette con i passaggi già ordinati, progetti, personaggi con versioni e zone, guide, percorso, tutorial, colori da comprare. Vanno importati così come sono, senza riscriverli a mano.

Il modulo NON è una copia del prototipo. Il prototipo salva i dati nel `localStorage` del browser; Monarch deve salvare, modificare e cancellare tutto da **database**, con la grafica e le convenzioni di Monarch.

La procedura per mettere online il nuovo script di deploy è in `DEPLOY-PASSAGGIO.md`.

## Integrazione in Monarch

- Segui **esattamente lo stesso schema dei moduli esistenti** (Palestra, Amministrazione). Prima di scrivere codice, studia come sono fatti:
  - registrazione del modulo e card nella schermata di scelta dopo il login (nome, colore e icona propri);
  - menu della sidebar che dipende dal modulo attivo;
  - pagina "Gestisci modulo" (nome, immagine, chi ha accesso);
  - permessi con Spatie;
  - comando "azzera dati modulo" riservato all'admin.
- Stack: Laravel + Breeze, Tailwind, Alpine.js, Spatie/laravel-permission. Niente nuovi framework frontend.
- Stile: lo stesso di Monarch (pulizia dell'app Fitness di iOS + densità dashboard stile Vyzor), responsive su telefono, tablet e desktop, tema chiaro e scuro. Le liste lunghe usano le data table già usate in Monarch.
- Nome del modulo: **3D - Resina**. Prefisso tabelle `resin_`, rotte `resina.*`, namespace `App\...\Resina` (adatta al pattern esistente).

### Deploy: attenzione

Il deploy avviene da webhook (`deploy.php`), che esegue `git pull` e `php artisan migrate --force`. Non c'è accesso SSH al server. Quindi:

- Le migration devono essere sicure e non distruttive.
- L'**import dei dati iniziali** non può dipendere da un `db:seed` lanciato a mano sul server. Deve partire da una **migration dati** che richiama un importer **idempotente** (`updateOrCreate` su `slug` o `code`), rieseguibile senza duplicare nulla. Crea anche il comando `php artisan resina:importa` per l'uso in locale.

## Chi vede cosa

- **Catalogo condiviso**, modificabile da chi ha il permesso di gestione del modulo: progetti, personaggi, versioni, zone, ricette, guide, tipi di armatura, tecniche, percorso, tutorial, colori da comprare.
- **Dati personali** di ogni utente:
  - inventario colori (quelli base importati + quelli aggiunti) e pennelli;
  - avanzamento del percorso e dei passaggi "fatto";
  - versione scelta per ogni personaggio;
  - miscele salvate nel mixer;
  - "Le mie figure", comprese quelle create da foto.
- All'attivazione del modulo per un utente, gli vengono assegnati i 28 colori e i 16 pennelli iniziali.

## Modello dati (proposta: adattala se serve, ma mantieni i concetti)

- `resin_paints` — catalogo colori: code, name, name_en, hex, line, type (`normal|metallic|wash|airbrush`), usage.
- `resin_user_paints` — inventario personale: user_id, paint_id, oppure dati di un colore personalizzato (name, code, hex, type).
- `resin_shop_suggestions` — colori consigliati da comprare: code, name, hex, why.
- `resin_brushes` — per utente: user_id, type (`tondo|liner|spot|piatto|angolato|drybrush`), size (stringa: `10/0`, `1`, `9`), metallic_only, position.
- `resin_recipe_categories`, `resin_recipes` (slug, category, title, who, tip, is_inline).
- `resin_recipe_steps` — recipe_id, position, role, usage, optional, technique, coverage.
- `resin_recipe_step_paints` — step_id, paint_id, drops.
  - I passaggi "inline" delle zone (`inline_steps` nel JSON) diventano ricette con `is_inline = true`, legate alla zona.
- `resin_projects` — slug, name, subtitle, theme, status (`completo|anteprima`), intro, armor_label, default_bases (json), cover image.
- `resin_character_groups` — project_id, slug, name, position.
- `resin_characters` — project_id (nullable per le figure personali), user_id (nullable: valorizzato per le figure personali), group, slug, name, alias_it, subtitle, search_query, versions_note, no_face, no_eyes, bases (json), tips (json), source (`catalogo|manuale|foto`), reference_image, note.
- `resin_character_versions` — character_id, slug, position, label, subtitle, note.
- `resin_zones` — character_id, version_id (nullable), position, name, tab (nullable), recipe_id (nullable), target_hex (nullable), note.
- `resin_armor_types` — project_id, slug, title, who, description, recipes (relazione), guide.
- `resin_guides` + `resin_guide_steps` (position, title, description) + step→paint con gocce.
- `resin_step_progress` — user_id, zone_id, step_position (oppure recipe_step_id), done_at.
- `resin_user_character_versions` — user_id, character_id, version_id.
- `resin_saved_mixes` — user_id, name, mix (json di code+gocce).
- `resin_path_steps` e `resin_user_path_progress`; `resin_tutorials`.
- Le tecniche (preparazione, primer, tavolozza bagnata, diluizione, BSL, TMM, wash, drybrush, velatura, spigoli, metallici, occhi, vernice, errori) possono restare **viste Blade statiche**, copiando testi e SVG da `reference.html`.

## Regole di calcolo (identiche al prototipo)

Metti la logica colore in un modulo JavaScript unico (es. `resources/js/resina/color.js`), usato da Alpine. Ricopia le funzioni da `reference.html`:

- **Miscela**: media geometrica pesata in luce lineare sRGB (`mixLin`). Il risultato è metallico se i colori metallici sono almeno il 30% delle gocce.
- **Distanza colore**: Lab con ΔE76. Vicinanza: `< 4` quasi identico, `< 10` vicino, altrimenti approssimativo.
- **Trova colore** (`findMixes`): esclude metallici, wash e airbrush. Prova:
  - singoli colori;
  - coppie con 1–8 gocce per colore;
  - terne con 1–5 gocce per colore (solo proporzioni ridotte ai minimi termini).
  - Punteggio: `ΔE + 0.9 × (numero colori − 1) + 0.05 × gocce totali`. Mostra i primi 6 risultati.
- **Ricetta automatica** da un colore obiettivo (`autoBSL`):
  - ombra = L −17, a e b × 0.92;
  - base = colore obiettivo;
  - luce = L +15, a e b × 0.82;
  - ciascuna calcolata con `findMixes`.
- **Colore da comprare**: se il ΔE della base è ≥ 10, suggerisci il colore più vicino di `shop_suggestions`, se l'utente non lo possiede già. Il pulsante "L'ho comprato" lo aggiunge all'inventario.
- **Ordine dei passaggi**: nel JSON è già quello di pittura (`position`); non riordinarlo. `optional`, `technique` e `coverage` sono già calcolati.
- **Scheda di una zona** (`tab`): se nel JSON è vuota, deducila dal nome come fa `zoneTab()`. Le schede sono, in quest'ordine, che è anche l'ordine di pittura:
  1. Panoramica
  2. Pelle
  3. Occhi e volto
  4. Tuta e vestiti
  5. Armatura (etichetta dal progetto: "Cloth" per Saint Seiya)
  6. Capelli
  7. Dettagli
  8. Basetta
  9. Reference
- **Versioni**: le zone della versione scelta **sostituiscono** quelle con lo stesso nome e si aggiungono alle altre (`charZones`). Se non c'è già una zona occhi e il personaggio non ha `no_face` né `no_eyes`, si aggiungono in automatico la zona "Occhi" (ricetta `eyes`) e la zona "Labbra, guance e sopracciglia" (ricetta `face`). Si aggiungono anche le basette: quelle del personaggio oppure quelle predefinite del progetto.
- **Avanzamento**: conta solo i passaggi non facoltativi ed esclude la basetta.
- **Pennelli**: ricopia `SLOTS`, `pickBrush`, `zoneSize`, `brushFor`, `brushChip`.
  - Ogni passaggio mostra il pennello scelto dall'inventario dell'utente, in base alla tecnica e alla dimensione della zona.
  - Se esiste un pennello marcato `metallic_only`, va usato nei passaggi metallici.

## Schermate

1. **Home del modulo**: mensola dei colori (tocca un flacone e va nel mixer), avanzamento del percorso, progetti, strumenti.
2. **Progetti**: elenco a card.
3. **Pagina progetto**: fascia a tema e sotto-schede Personaggi (filtri per gruppo + ricerca), Armature, Passo passo, Ricette del progetto, Riferimenti.
4. **Scheda personaggio**:
   - selettore di versione, tavolozza grande, schede nell'ordine di pittura con contatore "fatti/totali";
   - passaggi numerati con: facoltativo, tecnica (link alla tecnica), pennello, barra della superficie, formula in gocce con **codice colore sempre visibile accanto al nome**, hex, pulsante Mixer, casella "fatto";
   - Panoramica con: avanzamento, ordine di lavoro, colori che userai, pennelli che userai, attrezzatura, consigli, pulsanti "Copia nelle mie figure" e "Azzera i fatto".
5. **Ricettario**: filtri per categoria, ricerca, scala dei toni dal più scuro al più chiaro separata dall'ordine dei passaggi. CRUD delle ricette con editor dei passaggi (ruolo, uso, gocce per colore, riordino).
6. **Trova colore** e **Mixer** (con miscele salvate).
7. **Tecniche**, **Percorso principiante** (con spunte), **Tutorial** (ricerche YouTube), **Da comprare**.
8. **I miei colori** (inventario, aggiungi o rimuovi personalizzati) e **I miei pennelli** (modifica tipo, misura, "solo metallici", ripristina kit).
9. **Le mie figure**: CRUD. Ogni zona può avere una ricetta oppure un colore libero (ricetta automatica) e una scheda. Stessa vista a schede dei personaggi.
10. **Analizza foto**:
    - caricamento dell'immagine in storage, con miniatura;
    - estrazione dei colori principali lato client (k-means come `dominantColors`);
    - campionamento cliccando sull'immagine;
    - scelta del personaggio dal catalogo (con versione) oppure dati di un personaggio nuovo;
    - assegnazione dei colori alle zone e "Crea la guida", che crea una figura personale.
    - **Analisi con Claude (fase finale, facoltativa)**: chiamata server-side all'API Anthropic con chiave in `.env`, attivabile da configurazione. Usa lo stesso prompt e lo stesso formato JSON di `askClaude()` nel prototipo. Se la chiave non è configurata, il pulsante non compare e tutto il resto funziona.

## Fasi di lavoro

Lavora una fase alla volta. A fine fase: test, verifica nel browser, riepilogo di cosa è stato fatto, **commit in italiano** (raggruppati per argomento), poi **aspetta l'ok** prima della fase successiva.

0. Analisi del progetto e piano, senza scrivere codice.
1. Migration, model, relazioni, importer idempotente, migration dati, comando `resina:importa`.
2. Registrazione del modulo, permessi, card nella scelta modulo, sidebar, Gestisci modulo, azzera dati.
3. Inventario colori e pennelli, ricettario con CRUD.
4. Progetti, personaggi, versioni, zone (CRUD) e scheda personaggio a schede con avanzamento.
5. Trova colore, mixer, percorso, tecniche, tutorial, da comprare.
6. Le mie figure e Analizza foto (parte locale).
7. Analisi con Claude via API (facoltativa).
