# Modulo 3D - Resina — Versione 2: più semplice da usare

## Perché

Il modulo funziona, ma usandolo per dipingere davvero la scheda personaggio è risultata confusa:

- la versione si sceglie con pulsanti di testo;
- tavolozza, schede e passaggi compaiono tutti insieme;
- i passaggi dicono **cosa** fare (Base, Ombra, Luce) ma non **come**.

Chi inizia non riesce ad applicarli.

## Obiettivo

"Semplice" non vuol dire meno informazioni. Vuol dire **meno cose sullo schermo nello stesso momento**:

- un pezzo della figura alla volta;
- un passaggio alla volta;
- ogni passaggio completo: cosa preparare, come si fa, quanto aspettare, come deve venire, errori da evitare.

Le informazioni ci sono tutte, ma si scoprono quando servono.

Il riferimento per tono e livello di dettaglio dei testi è `data/technique_guides.json`, già pronto da importare.

## Principi visivi (validi per tutto il modulo)

- Una sola azione principale per schermata, con un pulsante grande in basso: «Inizia», «Fatto, avanti».
- Testi brevi, frasi complete, linguaggio da principiante. Sigle e codici sempre spiegati.
- Il codice del colore resta sempre accanto al nome, in piccolo.
- Le informazioni secondarie (scala dei toni, hex, Mixer, ruolo tecnico) stanno in una parte richiudibile «Dettagli», chiusa di default.
- Su telefono si usa con una mano: elementi da toccare grandi, niente righe dense di icone.
- Le pagine attuali più dense (scheda a schede, ricettario) restano raggiungibili come «Vista completa» per chi le vuole.

## A. Foto di riferimento obbligatoria per ogni versione

### Dati

- **Una versione per tutti.** Ogni personaggio del catalogo deve avere almeno una versione. Una migration crea la versione «Unica» per i personaggi che oggi non ne hanno, senza spostare le loro zone: le zone senza `version_id` restano comuni a tutte le versioni.
- **Foto sulla versione.** Su `resin_character_versions` aggiungi: `reference_image_path`, `reference_thumb_path`, `reference_source` (testo libero: da dove viene la foto), `reference_updated_at`.
- **Dove stanno le foto.** In storage privato, servite da una rotta autenticata per chi ha accesso al modulo. Sono foto del catalogo, quindi le vedono tutti gli utenti del modulo.
- **Ridimensionamento.** Nel browser, riusando `photo.js`: 1600 px per la foto, 400 px per la miniatura.

### Sezione «Foto di riferimento» (solo admin)

- Una griglia di tutti i personaggi, divisi per progetto e gruppo. Ogni versione ha:
  - stato: manca / presente;
  - miniatura;
  - pulsante Carica o Sostituisci;
  - campo Fonte;
  - link di ricerca già pronti (Google Immagini con la `search_query` del personaggio e l'etichetta della versione).
- Filtro «Solo mancanti» e contatore «N foto mancanti su M».
- Il contatore delle mancanti compare anche nella Home del modulo, solo per l'admin, con il link alla sezione.
- Esportazione dell'elenco delle foto mancanti in testo semplice (personaggio, versione, frase di ricerca), da copiare.
- Le foto le carica l'admin. Non scaricare immagini da internet in automatico.

### Obbligatorietà

- Creando una **versione nuova** dall'interfaccia, la foto è un campo obbligatorio.
- Per le versioni già importate senza foto: la scheda mostra al posto della foto un riquadro «Manca la foto di riferimento». Per l'admin c'è il pulsante per caricarla, per gli altri utenti la scritta «L'admin non l'ha ancora aggiunta».
- La guida funziona lo stesso: non si blocca nulla.

## B. Nuova scheda personaggio: la «Modalità pittura»

### Schermata 1 – Scegli la versione

- Card grandi, una per versione, con la **foto di riferimento** in evidenza, l'etichetta (es. «Anime V1») e la sottoetichetta (es. «Santuario»).
- Toccando una card si apre la nota della versione in 1–2 righe e il pulsante «Dipingi questa versione».
- Se il personaggio ha una sola versione, si salta direttamente alla schermata 2.
- La scelta si salva per utente, come ora.

### Schermata 2 – I pezzi della figura

- In alto: la foto della versione, piccola (toccandola si ingrandisce), e la barra di avanzamento complessivo.
- Sotto: un elenco di **pezzi** nell'ordine di pittura. Un pezzo corrisponde a una zona. I pezzi sono raggruppati con piccoli titoli: Preparazione, Pelle, Occhi e volto, Tuta e vestiti, Cloth/Armatura, Capelli, Dettagli, Basetta, Vernice.
- Ogni pezzo è una card con:
  - colore della base;
  - nome;
  - «N passaggi · circa X minuti», somma dei tempi di attesa;
  - stato: da fare / in corso (k di N) / fatto ✓.
- Il primo pezzo non finito è evidenziato con il pulsante «Continua da qui».
- «Preparazione» e «Vernice» sono due pezzi fissi, con i testi di `sessione` e delle tecniche del modulo.
- In fondo, richiudibili: Colori che userai, Pennelli che userai, Consigli, Reference. Il contenuto è quello della Panoramica attuale.

### Schermata 3 – Un pezzo, un passaggio alla volta

1. **Scheda iniziale «Prepara»**, prima del primo passaggio del pezzo:
   - cosa mettere sulla tavolozza (colori con codice e gocce, uniti per tutti i passaggi del pezzo);
   - cosa mettere sul piattino (i metallici);
   - quali pennelli tirare fuori.
2. **Una scheda per ogni passaggio**, a tutta larghezza, con in quest'ordine:
   - «Passaggio k di N» e una barra di avanzamento del pezzo;
   - **titolo semplice** (da `titoli_semplici`, es. «Scurisci gli incavi con il wash») e il ruolo tecnico in piccolo;
   - **Colori**: formula in gocce con swatch, nome e codice;
   - **Pennello**: nome dal kit dell'utente e motivo in una riga;
   - **Dove**: il testo `usage` del passaggio e la barra della superficie;
   - **Come si fa**: elenco numerato dalla tecnica (`come`), più la `preparazione` e le varianti `metallico` o `shade_tmm` quando servono. Per i passaggi Luce con colore metallico, mostra anche l'alternativa `light_metallico_drybrush`;
   - **Quanto aspettare**, con il pulsante **«Avvia timer»** (Alpine, nessuna dipendenza): conto alla rovescia visibile, suono e vibrazione alla fine se il browser lo permette;
   - **Come deve venire** (`risultato`);
   - **Errori da evitare** (`errori`), richiudibile;
   - **Dettagli** richiudibile: hex, scala dei toni, pulsante Mixer, link alla tecnica completa.
3. **Navigazione**:
   - in basso, pulsanti grandi «Indietro» e «Fatto, avanti». «Fatto, avanti» segna il passaggio e va al successivo;
   - i passaggi facoltativi hanno anche «Salta» e un'etichetta chiara «Facoltativo: puoi saltarlo nelle prime figure»;
   - su telefono si può passare da un passaggio all'altro anche scorrendo con il dito.
4. **Fine pezzo**: schermata «Pezzo finito ✓», con il prossimo pezzo e il pulsante per iniziarlo. Ricorda anche di lavare il pennello se il pezzo era metallico.
5. **Ripresa**: riaprendo un pezzo si riparte dal primo passaggio non fatto.

### Contenuti delle tecniche

- Importa `data/technique_guides.json` in una tabella, per esempio `resin_technique_guides`, con il codice della tecnica e i campi in JSON. Va modificabile dall'admin con un editor semplice: un campo di testo per riga.
- L'importer è idempotente come quello del catalogo, con una migration dati.
- La scelta di titolo semplice e varianti avviene in `steps.js`, accanto alla logica già esistente, con test.

### Cosa resta uguale

Avanzamento, pennelli, calcolo dei colori, zone automatiche, versioni e permessi: la logica attuale non cambia, cambia solo come viene presentata. L'avanzamento già salvato resta valido.

## C. «Le mie figure» da una foto, senza API a pagamento

### Il flusso

1. **Esporta il contesto.** Nel modulo, pulsante «Esporta contesto per Claude» in Le mie figure. Genera un testo da copiare che contiene:
   - il formato richiesto (`resina-figura/1`);
   - i colori dell'utente (codice, nome, hex, tipo);
   - i suoi pennelli;
   - gli slug delle ricette con titolo e categoria;
   - gli slug di progetti, personaggi e versioni del catalogo;
   - le categorie di scheda.
2. **Chiedi la guida in chat.** L'utente incolla quel testo in una chat con Claude, insieme alla foto della figura. Claude risponde con un file JSON nel formato `resina-figura/1`.
3. **Importa.** Nel modulo, pagina **«Importa figura»**: si carica il file JSON (oppure si incolla il testo) e, se si vuole, la stessa foto.
4. **Controlla e crea.** Prima di creare la figura il modulo mostra un'**anteprima** con:
   - pezzi e colori;
   - avvisi chiari: colori non posseduti con il pulsante «Da comprare», ricette sconosciute, campi mancanti.
   Poi il pulsante «Crea la figura» crea la figura con le sue zone. I passaggi personalizzati diventano ricette `is_inline`, come per le zone del catalogo.

### Il formato `resina-figura/1`

Un esempio completo è in `data/esempio-figura.json`.

**Regole di validazione:**

- `formato` deve essere esattamente `resina-figura/1`.
- `pezzi` deve contenere da 1 a 30 elementi. Ogni pezzo deve avere `nome` e `scheda`. `scheda` deve essere una di: pelle, volto, vestiti, armatura, capelli, dettagli, basetta.
- **Priorità di ogni pezzo:**
  1. se c'è `ricetta`, si usa la ricetta del catalogo con quello slug;
  2. altrimenti, se ci sono `passaggi`, si crea una ricetta propria;
  3. altrimenti, se c'è `colore_riferimento`, la ricetta si calcola in automatico, come per il colore libero.
- **Passaggi:** ogni passaggio deve avere `titolo` (usato come ruolo), `miscela` (lista di `{codice, gocce}` con gocce da 1 a 20) e `tecnica` (uno dei codici di `technique_guides`). `dove` e `facoltativo` sono opzionali.
- **Colori:** un `codice` non presente tra i colori dell'utente non blocca l'import. Mostra l'avviso, e se il codice è tra i colori consigliati, il pulsante «L'ho comprato».
- **Personaggio del catalogo:** `personaggio_catalogo` (`progetto/personaggio`) e `versione_catalogo` sono opzionali. Se ci sono, la figura parte dalle zone di quella versione e i `pezzi` con lo stesso nome le sostituiscono, come fa oggi «Crea la guida».
- **Testi:** `consigli`, `note` e `versione_vista` vanno nei consigli e nella nota della figura.
- **Sicurezza:** il JSON è un dato dell'utente. Valida tutto, limita le dimensioni (per esempio 200 KB) e non eseguire mai nulla di quello che contiene.

L'importer va scritto come servizio riutilizzabile, per esempio `FigureImporter`: una futura analisi con le API userà lo stesso formato e lo stesso servizio.

### Analisi automatica con le API (più avanti, facoltativa)

- Se in `.env` c'è la chiave API di Anthropic, la pagina Analizza foto mostra il pulsante «Analizza con Claude».
- Il server manda foto e contesto e riceve lo stesso JSON `resina-figura/1`.
- Il JSON passa da `FigureImporter` e dalla stessa anteprima.

## Fasi

Per ogni fase: test, verifica nel browser (telefono e desktop, tema chiaro e scuro), riepilogo, commit in italiano, poi aspetta l'ok.

- **8.0 – Proposta di interfaccia, senza codice.** Descrivi le schermate di B e la sezione di A: cosa c'è in ogni schermata, dall'alto in basso, su telefono e su desktop. Ricava i casi difficili dai dati veri: personaggio senza versioni, pezzo con un solo passaggio, pezzo tutto facoltativo, versione senza foto. Aspetta l'ok.
- **8.1 – Foto di riferimento.** Migration (versione «Unica», campi foto), sezione admin, contatore in Home, rotta protetta, test.
- **8.2 – Contenuti delle tecniche.** Tabella, importer con migration dati, editor admin, logica dei titoli semplici e delle varianti in `steps.js`, test.
- **8.3 – Modalità pittura.** Le schermate 1–3, il timer, la ripresa, «Vista completa». Verifica percorrendo per intero almeno Seiya (Anime V1) e Shaka su telefono.
- **9 – Import figura.** Esporta contesto, Importa figura con anteprima, `FigureImporter` e test con JSON validi e non validi.
- **10 (facoltativa) – Analisi con le API.**
