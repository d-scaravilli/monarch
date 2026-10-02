<?php

namespace App\Services\Resina;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Downloads one image from an address the admin pasted, and nothing
 * else: http/https only, every host resolved and checked to be public
 * (no private, local or reserved addresses — SSRF), the connection
 * pinned to the checked address, redirects followed by hand and checked
 * again, short timeouts, at most 10 MB, image types only.
 */
class RemoteImageFetcher
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_REDIRECTS = 3;

    private const TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    /**
     * @return array{bytes: string, type: string, host: string}
     *
     * @throws RuntimeException with a message for the admin
     */
    public function fetch(string $url): array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            [$host, $port, $ip] = $this->checkUrl($url);

            try {
                $response = Http::withOptions([
                    'allow_redirects' => false,
                    'connect_timeout' => 4,
                    'timeout' => 10,
                    // Connect to the address that was checked, not to a second DNS answer.
                    'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.(str_contains($ip, ':') ? '['.$ip.']' : $ip)]],
                    // Stop as soon as the headers say it's too big, and while downloading.
                    'on_headers' => function ($response) {
                        if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                            throw new RuntimeException('L\'immagine supera i 10 MB.');
                        }
                    },
                    'progress' => function ($total, $downloaded) {
                        if ($downloaded > self::MAX_BYTES) {
                            throw new RuntimeException('L\'immagine supera i 10 MB.');
                        }
                    },
                ])->withHeaders([
                    'Accept' => implode(', ', self::TYPES),
                    'User-Agent' => 'Mozilla/5.0 (Monarch; immagini di riferimento)',
                ])->get($url);
            } catch (\Throwable $e) {
                throw new RuntimeException($this->ownMessage($e) ?? 'Non riesco a scaricare l\'immagine da questo indirizzo: prova a copiare direttamente l\'immagine.');
            }

            if ($response->redirect()) {
                $location = $response->header('Location');
                if (! $location) {
                    throw new RuntimeException('Il sito ha risposto con un rinvio senza indirizzo.');
                }
                $url = $this->absoluteUrl($location, $url);

                continue;
            }

            if (! $response->successful()) {
                throw new RuntimeException('Il sito ha risposto con un errore ('.$response->status().'): prova a copiare direttamente l\'immagine.');
            }

            if ((int) $response->header('Content-Length') > self::MAX_BYTES) {
                throw new RuntimeException('L\'immagine supera i 10 MB.');
            }

            $type = strtolower(trim(explode(';', $response->header('Content-Type'))[0]));
            if (! in_array($type, self::TYPES, true)) {
                throw new RuntimeException('L\'indirizzo non porta a un\'immagine: con il tasto destro scegli «Copia indirizzo immagine», non quello della pagina.');
            }

            $bytes = $response->body();
            if (strlen($bytes) > self::MAX_BYTES) {
                throw new RuntimeException('L\'immagine supera i 10 MB.');
            }

            return ['bytes' => $bytes, 'type' => $type, 'host' => $host];
        }

        throw new RuntimeException('Troppi rinvii: prova a copiare direttamente l\'immagine.');
    }

    /**
     * @return array{0: string, 1: int, 2: string} host, port and the public IP to use
     */
    public function checkUrl(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Serve un indirizzo che inizia con http:// o https://.');
        }

        $host = strtolower(trim($parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            throw new RuntimeException('Questo indirizzo non è consentito.');
        }
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolve($host);

        if ($ips === []) {
            throw new RuntimeException('Non trovo il sito di questo indirizzo.');
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new RuntimeException('Questo indirizzo non è consentito.');
            }
        }

        return [$host, $port, $ips[0]];
    }

    public function isPublicIp(string $ip): bool
    {
        // An IPv6 address wrapping an IPv4 one (::ffff:127.0.0.1) is judged by the IPv4 part.
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $match)) {
            $ip = $match[1];
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) !== false;
    }

    /**
     * Every IPv4 and IPv6 address of a host name (overridden in tests).
     *
     * @return array<int, string>
     */
    protected function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null, $records)));
    }

    /**
     * Our own message, when one of the checks above aborted the transfer
     * (Guzzle wraps it in its own exceptions).
     */
    private function ownMessage(\Throwable $e): ?string
    {
        for ($current = $e; $current; $current = $current->getPrevious()) {
            if ($current instanceof RuntimeException && str_contains($current->getMessage(), '10 MB')) {
                return $current->getMessage();
            }
        }

        return null;
    }

    private function absoluteUrl(string $location, string $base): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return str_starts_with($location, '/') ? $origin.$location : $origin.'/'.$location;
    }
}
