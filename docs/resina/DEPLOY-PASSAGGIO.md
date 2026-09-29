# Messa online: nuovo script di deploy e modulo "3D - Resina"

Da eseguire quando il modulo è completo e testato sul branch `feature/modulo-resina`. Si fa **in due tempi**:

- **Tempo A** — solo il nuovo `public/deploy.php` arriva su master e viene collaudato.
- **Tempo B** — merge del modulo.

Perché due tempi: se il modulo arrivasse insieme allo script, un eventuale revert del merge rimetterebbe online anche il **vecchio** `deploy.php` (quello con il token vecchio scritto dentro) e il webhook smetterebbe di funzionare proprio nel momento del bisogno. Così invece lo script resta su master qualunque cosa succeda al modulo.

## Cosa fa il nuovo deploy.php

- Legge il token da `DEPLOY_TOKEN` nel `.env` (se manca risponde 500) e lo confronta con `hash_equals`.
- Accetta solo POST (405), rifiuta token sbagliati (403) e deploy contemporanei (409, lock file).
- Aggiorna il codice con `git fetch origin master` + `git reset --hard origin/master`: una modifica locale sul server non blocca più il deploy. `.env`, `storage/` (comprese le foto delle figure) e `vendor/` non sono tracciati da Git e restano intatti.
- Esegue `/usr/local/bin/php artisan migrate --force` e `optimize:clear`, fermandosi al primo errore.
- Scrive ogni passo con data e ora in `storage/logs/deploy.log` e risponde con un riepilogo.

Il deploy **non** esegue `composer install`: niente nuove dipendenze Composer.

## Prima di iniziare

- Sul branch: test tutti verdi e build committata (verificato nel controllo finale).
- Tieni aperti: GitHub (Settings → Webhooks del repository), il File Manager di Netsons e GitKraken.
- Facoltativo ma consigliato: da phpMyAdmin di Netsons, esporta un backup del database (Esporta → Rapido → SQL).

## Tempo A — nuovo script di deploy

### A1. Genera il token nuovo

Nel terminale del Mac:

```
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Esce una stringa di 64 caratteri (0-9, a-f), sicura da mettere in un URL. Salvala nel gestore password.

### A2. Metti il token nel `.env` del server

File Manager → apri il `.env` nella cartella del progetto (non quello in `public`) → aggiungi in fondo:

```
DEPLOY_TOKEN=il_token_generato
```

Salva. Le virgolette non servono.

### A3. Carica il nuovo `public/deploy.php`

File Manager → sostituisci `public/deploy.php` del server con quello del repo (è identico su `feature/modulo-resina`). Da qui il token vecchio non funziona più.

### A4. Aggiorna l'URL del webhook

GitHub → repository → **Settings** → **Webhooks** → il webhook → **Edit** → nel *Payload URL* sostituisci il valore dopo `?token=` con il token nuovo → **Update webhook**.

### A5. Porta lo script su master (cherry-pick) e fai il push

In GitKraken:

1. Fai checkout di `master` (doppio clic sul branch) e **Pull**.
2. Nel grafo trova il commit **«Nuovo script di deploy: token da .env, lock, fetch/reset, log»** sul branch `feature/modulo-resina`.
3. Tasto destro → **Cherry pick commit** → conferma.
4. **Push**.

Da terminale sarebbe: `git switch master && git pull && git cherry-pick b11a694 && git push`.

### A6. Controlla il primo deploy con lo script nuovo

GitHub → Webhooks → il webhook → **Recent Deliveries** → l'ultima consegna → scheda **Response**:

```
Deploy completato
git fetch: OK
git reset: OK
migrate: OK
optimize:clear: OK
```

Se la consegna è rossa o non è partita: apri la consegna e premi **Redeliver**. Se GitHub segnala un timeout (aspetta al massimo 10 secondi) il deploy continua lo stesso: l'esito è in `storage/logs/deploy.log` (File Manager).

Controlla che l'app funzioni come prima (Palestra e Amministrazione): in questo tempo non cambia nient'altro.

## Tempo B — modulo "3D - Resina"

### B1. Merge e push

In GitKraken:

1. Checkout di `master` e **Pull**.
2. Tasto destro su `feature/modulo-resina` → **Merge feature/modulo-resina into master**.
3. **Push**.

Da terminale: `git switch master && git pull && git merge feature/modulo-resina && git push`.

Il commit dello script c'è già su master (dal cherry-pick): Git se ne accorge e il merge non lo tocca.

### B2. Controlla il deploy

Recent Deliveries → l'ultima consegna → Response: `Deploy completato` con `migrate: OK`. Le migration del modulo creano 26 tabelle `resin_*`, importano il catalogo e registrano il modulo; sul resto del database cambia solo una riga nella tabella dei moduli (verificato in locale su una copia nello stato della produzione).

### B3. Controlla l'app

1. Accedi come admin: nella scelta modulo c'è **3D - Resina** (viola, icona pennello).
2. Entra: la Home mostra 28 flaconi e 16 pennelli e i progetti Saint Seiya, Power Rangers e Marvel.
3. Apri un personaggio (es. Seiya), spunta un «fatto», apri Ricettario e Mixer.
4. Palestra e Amministrazione funzionano come prima.

## Piano di emergenza

### Il deploy non parte o fallisce

- **Dove guardare:** GitHub → Recent Deliveries → consegna → Response; `storage/logs/deploy.log` e `storage/logs/laravel.log` dal File Manager.
- **403 Accesso negato:** il token nell'URL del webhook non coincide con `DEPLOY_TOKEN` del `.env` (spazi, a capo, virgolette sbagliate). Correggi e **Redeliver**.
- **500 Configurazione mancante:** `DEPLOY_TOKEN` non è nel `.env` del server.
- **409 deploy già in corso:** aspetta un minuto e **Redeliver**. Se resta bloccato, cancella `storage/logs/deploy.lock` dal File Manager.
- **git fetch / git reset: ERRORE:** problema di Git sul server (connessione a GitHub, permessi). Il codice online non è cambiato. Leggi il messaggio nel log.
- **migrate: ERRORE:** il codice nuovo è online ma una migration non è andata. Il messaggio esatto è nel log. Se non si risolve subito, torna alla versione precedente (sotto).

Per **ripartire** in ogni caso: GitHub → Webhooks → Recent Deliveries → l'ultima consegna → **Redeliver**.

### Tornare alla versione precedente (revert del merge)

Da fare solo nel **Tempo B**: annulla il modulo, lasciando al suo posto il nuovo script di deploy.

In GitKraken:

1. Checkout di `master` e **Pull**.
2. Nel grafo trova il commit di merge **«Merge branch 'feature/modulo-resina'»** (quello del Tempo B).
3. Tasto destro → **Revert commit**. Se GitKraken chiede quale genitore tenere, scegli il **primo** (quello di `master`).
4. **Push**: il webhook fa il deploy della versione precedente da solo. Controlla Recent Deliveries e, se serve, **Redeliver**.

Da terminale: `git switch master && git pull && git revert -m 1 <hash del merge> && git push`.

Cosa resta dopo il revert:

- Le tabelle `resin_*` restano nel database: il codice vecchio le ignora, non danno fastidio.
- La card «3D - Resina» resta visibile all'admin nella scelta modulo ma non porta da nessuna parte. Per nasconderla: Gestisci del modulo → togli la spunta «Modulo attivo» (il codice vecchio non conosce l'icona pennello: scegline un'altra per poter salvare).
- Le foto già caricate restano in `storage/app/private/resina/`.

**Per rimettere online il modulo dopo averlo corretto** non basta rifare il merge (Git lo considera già unito): in GitKraken fai **Revert** del commit di revert («Revert "Merge branch …"»), poi merge dei commit di correzione, poi Push.

### Se una migration si è fermata a metà

MySQL non annulla le `CREATE TABLE` già eseguite. Se il log dice che una tabella esiste già (`Base table or view already exists`), da phpMyAdmin elimina **solo quella tabella `resin_…`** indicata nel messaggio e fai **Redeliver**: le migration riprendono da dove si erano fermate. L'import del catalogo si può rieseguire senza creare doppioni. Non toccare mai tabelle senza il prefisso `resin_`.

## Il token vecchio nella cronologia di Git

Basta averlo cambiato: il nuovo `deploy.php` non lo accetta più e non contiene segreti, quindi non serve riscrivere la cronologia. Verifica solo che quella stringa non sia usata come password da nessun'altra parte.

## Ultimo punto da fare (dopo la messa online, quando tutto funziona)

- [ ] Aggiungere il **Secret** del webhook GitHub con verifica della firma `X-Hub-Signature-256` in `deploy.php`, così il token non passa più nell'URL (e non finisce nei log del server).
