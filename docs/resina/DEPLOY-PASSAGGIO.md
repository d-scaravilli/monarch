# Messa online del modulo "3D - Resina"

Il sistema di deploy **resta quello attuale**: il webhook GitHub chiama il `public/deploy.php` del server, che esegue `git pull origin master` e poi `php artisan migrate --force`. Per andare online basta **merge su master e push**.

## Perché il pull non si blocca

Il `public/deploy.php` del server è modificato rispetto a Git. `git pull` si blocca solo se i commit in arrivo toccano un file modificato sul server: il branch `feature/modulo-resina` **non modifica `public/deploy.php`** (è identico a master), quindi il pull passa.

Se in futuro un commit toccasse `public/deploy.php`, il pull si bloccherebbe con «Your local changes to the following files would be overwritten by merge»: in quel caso vedi «Miglioria futura» qui sotto.

## Prima di iniziare

- Sul branch: test tutti verdi e build committata (verificato nel controllo finale).
- Tieni aperti GitHub (repository → **Settings** → **Webhooks**) e GitKraken.
- Facoltativo ma consigliato: da phpMyAdmin di Netsons esporta un backup del database (Esporta → Rapido → SQL).

## 1. Merge e push

In GitKraken:

1. Checkout di `master` (doppio clic sul branch) e **Pull**.
2. Tasto destro su `feature/modulo-resina` → **Merge feature/modulo-resina into master**.
3. **Push**.

Da terminale: `git switch master && git pull && git merge feature/modulo-resina && git push`.

## 2. Controlla il deploy

GitHub → Webhooks → il webhook → **Recent Deliveries** → l'ultima consegna → scheda **Response**. Deve mostrare l'esito di `git pull` (l'elenco dei file aggiornati) e di `migrate`, con le migration `resin_*`, `import_resina_catalog` e `create_resina_module` tutte `DONE`.

Le migration creano 26 tabelle `resin_*`, importano il catalogo e registrano il modulo. Sul resto del database cambia solo una riga nella tabella dei moduli: verificato in locale su una copia nello stato della produzione.

Se GitHub segnala un timeout (aspetta al massimo 10 secondi), il deploy può essere comunque andato a buon fine: controlla l'app. Se la consegna non è partita o è fallita: apri la consegna e premi **Redeliver**.

## 3. Controlla l'app

1. Accedi come admin: nella scelta modulo c'è **3D - Resina** (viola, icona pennello).
2. Entra: la Home mostra 28 flaconi e 16 pennelli e i progetti Saint Seiya, Power Rangers e Marvel.
3. Apri un personaggio (es. Seiya), spunta un «fatto», apri Ricettario e Mixer.
4. Palestra e Amministrazione funzionano come prima.

## 4. Dai l'accesso agli altri utenti

L'admin vede sempre il modulo. Per gli altri, uno dei due:

- 3D - Resina → **Gestisci** (su telefono è in «Altro») → **Accessi** → «Concedi accesso a» → **Aggiungi**;
- Amministrazione → **Utenti** → modifica l'utente → spunta **3D - Resina** tra i moduli → Salva.

Al primo ingresso ogni utente riceve da solo i 28 colori e i 16 pennelli. Solo l'admin modifica il catalogo.

## Piano di emergenza

### Il deploy non parte o fallisce

- **Dove guardare:** GitHub → Recent Deliveries → consegna → Response; `storage/logs/laravel.log` dal File Manager.
- **Il pull si blocca** («would be overwritten by merge» o conflitti): il messaggio elenca i file modificati sul server che il merge vuole aggiornare. Il codice online non è cambiato.
- **Migrate con errore:** il codice nuovo è online ma una migration non è andata; il messaggio esatto è nella Response. Se non si risolve subito, torna alla versione precedente (sotto).

Per **ripartire** in ogni caso: GitHub → Webhooks → Recent Deliveries → l'ultima consegna → **Redeliver**.

### Tornare alla versione precedente (revert del merge)

In GitKraken:

1. Checkout di `master` e **Pull**.
2. Nel grafo trova il commit di merge **«Merge branch 'feature/modulo-resina'»**.
3. Tasto destro → **Revert commit**. Se GitKraken chiede quale genitore tenere, scegli il **primo** (quello di `master`).
4. **Push**: il webhook fa pull e migrate da solo. Controlla Recent Deliveries e, se serve, **Redeliver**.

Da terminale: `git switch master && git pull && git revert -m 1 <hash del merge> && git push`.

Dopo il revert master torna identico a com'era prima del merge (provato in una copia del repository). Cosa resta:

- Le tabelle `resin_*` restano nel database: il codice vecchio le ignora, non danno fastidio.
- La card «3D - Resina» resta visibile all'admin nella scelta modulo ma non porta da nessuna parte. Per nasconderla: Gestisci del modulo → togli la spunta «Modulo attivo» (il codice vecchio non conosce l'icona pennello: scegline un'altra per poter salvare).
- Le foto già caricate restano in `storage/app/private/resina/`.

**Per rimettere online il modulo dopo averlo corretto** non basta rifare il merge (Git lo considera già unito): in GitKraken fai **Revert** del commit di revert («Revert "Merge branch …"»), poi merge dei commit di correzione, poi Push.

### Se una migration si è fermata a metà

MySQL non annulla le `CREATE TABLE` già eseguite. Se il messaggio dice che una tabella esiste già (`Base table or view already exists`), da phpMyAdmin elimina **solo quella tabella `resin_…`** indicata e fai **Redeliver**: le migration riprendono da dove si erano fermate. L'import del catalogo si può rieseguire senza creare doppioni. Non toccare mai tabelle senza il prefisso `resin_`.

## Consigliato anche senza cambiare sistema: cambia il token

Il `public/deploy.php` in Git contiene un token scritto in chiaro, quindi è nella cronologia del repository. Senza toccare il sistema di deploy puoi comunque renderlo inutile:

1. Genera un token nuovo nel terminale del Mac: `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`
2. File Manager → `public/deploy.php` del server → sostituisci il valore di `$secret` con il token nuovo → salva.
3. GitHub → Webhooks → il webhook → **Edit** → nel *Payload URL* metti il token nuovo dopo `?token=` → **Update webhook**.
4. Prova con **Redeliver** sull'ultima consegna: deve rispondere come sempre (non 403).

Il file del server è già modificato rispetto a Git, quindi questa modifica non cambia nulla per i pull.

## Miglioria futura: nuovo script di deploy

Pronto nella cronologia di Git: commit **`b11a694`** «Nuovo script di deploy: token da .env, lock, fetch/reset, log» (annullato sul branch prima della messa online). Rispetto all'attuale:

- token letto da `DEPLOY_TOKEN` nel `.env` e confrontato con `hash_equals`, niente segreti nel file;
- lock file contro due deploy contemporanei;
- `git fetch` + `git reset --hard origin/master` invece di `git pull`: una modifica locale sul server non blocca più il deploy (`.env`, `storage/` e `vendor/` non sono tracciati e restano intatti);
- `migrate --force` e `optimize:clear`, fermandosi al primo errore;
- ogni passo con data e ora in `storage/logs/deploy.log`.

Quando lo si vorrà adottare, conviene farlo **da solo**, senza altre modifiche nello stesso rilascio:

1. Aggiungi `DEPLOY_TOKEN=<token nuovo>` al `.env` del server.
2. Carica il nuovo `public/deploy.php` dal File Manager (quello del commit `b11a694`).
3. Aggiorna l'URL del webhook su GitHub con il token nuovo.
4. Su master: **cherry-pick** di `b11a694` in GitKraken (tasto destro → Cherry pick commit) e **Push**.
5. Controlla in Recent Deliveries: `Deploy completato` con `git fetch`, `git reset`, `migrate` e `optimize:clear` tutti `OK`.

Dopo, come ulteriore passo: aggiungere il **Secret** del webhook GitHub con verifica della firma `X-Hub-Signature-256`, così il token non passa più nell'URL (e non finisce nei log del server).
