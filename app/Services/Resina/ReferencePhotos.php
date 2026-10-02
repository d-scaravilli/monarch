<?php

namespace App\Services\Resina;

use App\Models\Resina\CharacterVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reference photos of the catalog versions. They live on the private
 * disk and reach the browser only through an authenticated route.
 *
 * Every upload goes through a temporary token first (resized photo,
 * thumbnail and source), then is attached to a version — so the same
 * flow serves an existing version and a version that's still a form.
 */
class ReferencePhotos
{
    public const PHOTO_MAX = 1600;

    public const THUMB_MAX = 400;

    /** Larger images would need too much memory to decode. */
    private const MAX_PIXELS = 50_000_000;

    private const TEMP_DIR = 'resina/references/tmp';

    public function __construct(private RemoteImageFetcher $fetcher) {}

    /**
     * Photo and thumbnail already resized in the browser.
     */
    public function storeUploaded(UploadedFile $photo, UploadedFile $thumb, ?string $source): string
    {
        $token = $this->newToken();
        $photo->storeAs(self::TEMP_DIR, $token.'.jpg', 'local');
        $thumb->storeAs(self::TEMP_DIR, $token.'-miniatura.jpg', 'local');
        $this->writeMeta($token, $source);

        return $token;
    }

    /**
     * An address pasted by the admin: download it safely, then resize it
     * here with GD. Without GD (or for a format GD can't read) the image
     * goes back to the browser, which resizes it with photo.js.
     *
     * @return array{token: ?string, source: string, dataUrl: ?string}
     */
    public function storeFromUrl(string $url): array
    {
        $image = $this->fetcher->fetch($url);
        $source = preg_replace('/^www\./', '', $image['host']);

        $resized = $this->resizeWithGd($image['bytes']);

        if ($resized === null) {
            return ['token' => null, 'source' => $source, 'dataUrl' => 'data:'.$image['type'].';base64,'.base64_encode($image['bytes'])];
        }

        $token = $this->newToken();
        Storage::disk('local')->put($this->tempPath($token), $resized['photo']);
        Storage::disk('local')->put($this->tempPath($token, true), $resized['thumb']);
        $this->writeMeta($token, $source);

        return ['token' => $token, 'source' => $source, 'dataUrl' => null];
    }

    public function temporaryExists(string $token): bool
    {
        return Str::isUuid($token) && Storage::disk('local')->exists($this->tempPath($token));
    }

    public function tempPath(string $token, bool $thumb = false): string
    {
        return self::TEMP_DIR.'/'.$token.($thumb ? '-miniatura' : '').'.jpg';
    }

    /**
     * Move a temporary photo onto a version, replacing the previous one.
     */
    public function attach(CharacterVersion $version, string $token, ?string $source = null): void
    {
        if (! $this->temporaryExists($token)) {
            throw new RuntimeException('La foto caricata non è più disponibile: caricala di nuovo.');
        }

        $disk = Storage::disk('local');
        $base = 'resina/references/'.$version->character_id.'/'.$version->id.'-'.Str::random(8);
        $meta = json_decode($disk->get(self::TEMP_DIR.'/'.$token.'.json') ?? '{}', true) ?: [];
        $old = array_filter([$version->reference_image_path, $version->reference_thumb_path]);

        $disk->move($this->tempPath($token), $base.'.jpg');
        $disk->move($this->tempPath($token, true), $base.'-miniatura.jpg');
        $disk->delete(self::TEMP_DIR.'/'.$token.'.json');

        $version->update([
            'reference_image_path' => $base.'.jpg',
            'reference_thumb_path' => $base.'-miniatura.jpg',
            'reference_source' => $source ?? ($meta['source'] ?? null),
            'reference_updated_at' => now(),
        ]);

        $disk->delete($old);
    }

    /**
     * The files of versions about to be deleted (with their character
     * or project).
     *
     * @param  Collection<int, CharacterVersion>  $versions
     */
    public function deleteFiles(Collection $versions): void
    {
        Storage::disk('local')->delete($versions->flatMap(fn (CharacterVersion $version) => [$version->reference_image_path, $version->reference_thumb_path])->filter()->values()->all());
    }

    /**
     * Temporary uploads nobody attached: removed after a day, on the next
     * upload (no scheduler needed on the server).
     */
    public function cleanTemporary(): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::TEMP_DIR) as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }

    /**
     * @return array{photo: string, thumb: string}|null JPEGs, or null when GD can't do it
     */
    public function resizeWithGd(string $bytes): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $size = @getimagesizefromstring($bytes);
        if (! $size) {
            throw new RuntimeException('Il file scaricato non è un\'immagine leggibile.');
        }
        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw new RuntimeException('L\'immagine è troppo grande da elaborare: scegline una più piccola.');
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            return null;
        }

        try {
            return ['photo' => $this->jpeg($image, self::PHOTO_MAX, 85), 'thumb' => $this->jpeg($image, self::THUMB_MAX, 75)];
        } finally {
            imagedestroy($image);
        }
    }

    private function jpeg(\GdImage $image, int $max, int $quality): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $max / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        // Transparent areas (PNG, WebP) become white, not black.
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        ob_start();
        imagejpeg($target, null, $quality);
        imagedestroy($target);

        return (string) ob_get_clean();
    }

    private function newToken(): string
    {
        $this->cleanTemporary();

        return (string) Str::uuid();
    }

    private function writeMeta(string $token, ?string $source): void
    {
        Storage::disk('local')->put(self::TEMP_DIR.'/'.$token.'.json', json_encode(['source' => $source]));
    }
}
