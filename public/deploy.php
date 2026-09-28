<?php

/**
 * Webhook di deploy chiamato da GitHub a ogni push su master.
 *
 * Non avvia Laravel: legge DEPLOY_TOKEN direttamente dal file .env,
 * aggiorna il codice con fetch + reset --hard (una modifica locale sul
 * server non blocca più il deploy; .env, storage e vendor non sono
 * tracciati da Git e restano intatti), esegue le migration e svuota le
 * cache. Ogni esito finisce in storage/logs/deploy.log.
 */
$root = dirname(__DIR__);
$php = '/usr/local/bin/php';
$logFile = $root.'/storage/logs/deploy.log';
$lockFile = $root.'/storage/logs/deploy.lock';

date_default_timezone_set('Europe/Rome');
header('Content-Type: text/plain; charset=utf-8');

/**
 * Legge una variabile dal file .env senza dipendenze: KEY=valore,
 * con virgolette facoltative. Restituisce null se manca o è vuota.
 */
function readEnvValue(string $envPath, string $key): ?string
{
    if (! is_readable($envPath)) {
        return null;
    }

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, $key.'=')) {
            continue;
        }

        $value = trim(substr($line, strlen($key) + 1));
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return $value !== '' ? $value : null;
    }

    return null;
}

function writeLog(string $logFile, string $message): void
{
    file_put_contents($logFile, '['.date('Y-m-d H:i:s').'] '.$message.PHP_EOL, FILE_APPEND | LOCK_EX);
}

$expectedToken = readEnvValue($root.'/.env', 'DEPLOY_TOKEN');

if ($expectedToken === null) {
    http_response_code(500);
    exit('Configurazione mancante');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo non permesso');
}

$givenToken = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';

if (! hash_equals($expectedToken, $givenToken)) {
    http_response_code(403);
    exit('Accesso negato');
}

$lock = fopen($lockFile, 'c');

if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
    http_response_code(409);
    writeLog($logFile, 'Deploy rifiutato: un altro deploy è già in corso.');
    exit('Un altro deploy è già in corso');
}

// GitHub chiude la connessione dopo 10 secondi: il deploy deve
// comunque arrivare in fondo anche se nessuno aspetta più la risposta.
ignore_user_abort(true);
set_time_limit(300);

$steps = [
    'git fetch' => 'git fetch origin master',
    'git reset' => 'git reset --hard origin/master',
    'migrate' => $php.' artisan migrate --force',
    'optimize:clear' => $php.' artisan optimize:clear',
];

writeLog($logFile, '=== Deploy avviato ===');
$summary = [];
$failed = false;

foreach ($steps as $label => $command) {
    $output = [];
    $exitCode = 0;
    exec('cd '.escapeshellarg($root).' && '.$command.' 2>&1', $output, $exitCode);

    $status = $exitCode === 0 ? 'OK' : "ERRORE (codice {$exitCode})";
    writeLog($logFile, "{$label}: {$status}".PHP_EOL.implode(PHP_EOL, $output));
    $summary[] = "{$label}: {$status}";

    // Un passo fallito ferma tutto: niente migration su codice non aggiornato.
    if ($exitCode !== 0) {
        $failed = true;
        break;
    }
}

writeLog($logFile, $failed ? '=== Deploy fallito ===' : '=== Deploy completato ===');

flock($lock, LOCK_UN);
fclose($lock);

http_response_code($failed ? 500 : 200);
echo ($failed ? 'Deploy fallito' : 'Deploy completato').PHP_EOL.implode(PHP_EOL, $summary).PHP_EOL;
