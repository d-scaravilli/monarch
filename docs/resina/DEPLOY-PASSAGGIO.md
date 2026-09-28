# Passaggio al nuovo script di deploy

Da eseguire **una sola volta, alla fine**, quando il modulo "3D - Resina" è completo e testato sul branch `feature/modulo-resina`.

## Cosa cambia

Il `public/deploy.php` del server oggi fa `git pull` + `migrate --force`, con il token scritto nel file. Il nuovo script:

- legge il token da `DEPLOY_TOKEN` nel `.env` (se manca risponde 500) e lo confronta con `hash_equals`;
- accetta solo POST (405), rifiuta token sbagliati (403) e deploy contemporanei (409, lock file);
- aggiorna il codice con `git fetch origin master` + `git reset --hard origin/master`: una modifica locale sul server non blocca più il deploy. `.env`, `storage/` e `vendor/` non sono tracciati da Git e restano intatti;
- esegue `/usr/local/bin/php artisan migrate --force` e `optimize:clear`, fermandosi al primo errore;
- scrive ogni passo con data e ora in `storage/logs/deploy.log` e risponde con un riepilogo.

Il deploy **non** esegue `composer install`: niente nuove dipendenze Composer.

## Perché si sostituisce il file a mano

Il `deploy.php` del server è diverso da quello in Git, quindi un `git pull` che porta il file nuovo si bloccherebbe. Invece di sbloccarlo con lo script vecchio, si carica direttamente il file nuovo dal File Manager: da quel momento il token vecchio non vale più, e il `reset --hard` del nuovo script non si blocca mai per modifiche locali.

## Procedura

### 1. Genera il token nuovo

Nel terminale del Mac:

```
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Esce una stringa di 64 caratteri (0-9, a-f), sicura da mettere in un URL. Salvala nel gestore password.

### 2. Metti il token nel `.env` del server

File Manager di Netsons → apri il `.env` nella cartella del progetto (non quello in `public`) → aggiungi in fondo:

```
DEPLOY_TOKEN=il_token_generato
```

Salva. Le virgolette non servono.

### 3. Merge e push

```
git switch master
git merge feature/modulo-resina
git push origin master
```

GitHub chiama il webhook con il token vecchio: con lo script vecchio il pull si blocca e la migrate non trova nulla da fare; se il file nuovo è già caricato risponde 403. In entrambi i casi non si rompe niente.

### 4. Carica il nuovo `public/deploy.php`

File Manager → sostituisci `public/deploy.php` del server con quello del repo (branch master). Da qui il token vecchio non funziona più.

### 5. Aggiorna l'URL del webhook su GitHub

Repository → **Settings** → **Webhooks** → il webhook → **Edit** → nel *Payload URL* sostituisci il valore dopo `?token=` con il token nuovo → **Update webhook**.

### 6. Rilancia il deploy (Redeliver)

Stesso webhook → scheda **Recent Deliveries** → apri l'ultima consegna (quella del push) → **Redeliver** → conferma.

Nella scheda **Response** della nuova consegna deve comparire:

```
Deploy completato
git fetch: OK
git reset: OK
migrate: OK
optimize:clear: OK
```

Se GitHub segnala un timeout (aspetta al massimo 10 secondi), il deploy continua lo stesso: l'esito è in `storage/logs/deploy.log`, leggibile dal File Manager.

### 7. Controlla

Accedi come admin: nella scelta modulo compare "3D - Resina".

Redeliver serve anche in futuro, ogni volta che vuoi ripetere un deploy.

## Il token vecchio nella cronologia di Git

Basta averlo cambiato: il nuovo `deploy.php` non lo accetta più e non contiene segreti, quindi non serve riscrivere la cronologia. Verifica solo che quella stringa non sia usata come password da nessun'altra parte.

## Ultimo punto da fare (dopo il passaggio, quando tutto funziona)

- [ ] Aggiungere il **Secret** del webhook GitHub con verifica della firma `X-Hub-Signature-256` in `deploy.php`, così il token non passa più nell'URL (e non finisce nei log del server).
