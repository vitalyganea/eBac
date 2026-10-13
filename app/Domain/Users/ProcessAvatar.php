<?php

namespace App\Domain\Users;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Prelucrează o imagine de profil încărcată.
 *
 * Principiul de bază: **fișierul primit nu se stochează niciodată.** Îl decodăm
 * în pixeli și scriem o imagine complet nouă. Asta rezolvă dintr-o singură
 * mișcare trei probleme:
 *
 *  1. Fișierele „poliglote" — o imagine validă care conține și cod executabil —
 *     nu supraviețuiesc reencodării.
 *  2. Metadatele EXIF dispar. Contează foarte mult aici: o poză făcută cu
 *     telefonul conține de obicei **coordonate GPS**, iar utilizatorii noștri
 *     sunt minori. Un avatar nu are de ce să spună unde locuiește cineva.
 *  3. Validarea devine reală: dacă GD nu poate decoda fișierul, nu e o imagine,
 *     indiferent ce spune extensia sau antetul trimis de browser.
 *
 * Generăm două dimensiuni. Un avatar de 256px servit într-o casetă de 24px din
 * antet ar fi risipă pe date mobile — exact publicul nostru.
 */
final class ProcessAvatar
{
    private const SIZES = [64, 256];

    private const DISK = 'public';

    /** Peste asta, imaginea sursă e refuzată înainte de decodare. */
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Limită de siguranță la decodare: o imagine de 20.000×20.000 ar epuiza memoria. */
    private const MAX_PIXELS = 40_000_000;

    public function __invoke(User $user, UploadedFile $file): string
    {
        $image = $this->decode($file);

        try {
            $square = $this->cropToSquare($image);

            $path = $this->generatePath();
            $this->deleteFiles($user->avatar_path);

            foreach (self::SIZES as $size) {
                $resized = imagescale($square, $size, $size, IMG_BICUBIC);

                if ($resized === false) {
                    throw new RuntimeException('Redimensionarea a eșuat.');
                }

                ob_start();
                imagewebp($resized, null, 82);
                $binary = (string) ob_get_clean();
                imagedestroy($resized);

                Storage::disk(self::DISK)->put("{$path}-{$size}.webp", $binary);
            }

            imagedestroy($square);

            return $path;
        } finally {
            if (is_resource($image) || $image instanceof \GdImage) {
                @imagedestroy($image);
            }
        }
    }

    public function delete(User $user): void
    {
        $this->deleteFiles($user->avatar_path);
    }

    /**
     * Decodează fișierul — singura validare în care avem încredere.
     *
     * Extensia și antetul MIME trimis de browser sunt controlate de client, deci
     * nu dovedesc nimic.
     */
    private function decode(UploadedFile $file): \GdImage
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('Imaginea depășește 2 MB.');
        }

        $info = @getimagesize($file->getRealPath());

        if ($info === false) {
            throw new RuntimeException('Fișierul nu este o imagine validă.');
        }

        [$width, $height, $type] = $info;

        if ($width * $height > self::MAX_PIXELS) {
            throw new RuntimeException('Imaginea are rezoluție prea mare.');
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Formatul imaginii nu este acceptat. Folosește JPEG, PNG sau WebP.');
        }

        return $this->applyExifOrientation($image, $file, $type);
    }

    /**
     * Rotește imaginea conform EXIF, ÎNAINTE de a arunca metadatele.
     *
     * Telefoanele salvează adesea fotografia în orientarea senzorului și notează
     * rotația în EXIF. Dacă am ignora-o, portretele ar apărea culcate.
     */
    private function applyExifOrientation(\GdImage $image, UploadedFile $file, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = $exif['Orientation'] ?? 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated ?: $image;
    }

    /** Decupare centrată la pătrat — un avatar dreptunghiular ar fi deformat. */
    private function cropToSquare(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $side = min($w, $h);

        $cropped = imagecrop($image, [
            'x' => (int) (($w - $side) / 2),
            'y' => (int) (($h - $side) / 2),
            'width' => $side,
            'height' => $side,
        ]);

        if ($cropped === false) {
            throw new RuntimeException('Decuparea imaginii a eșuat.');
        }

        return $cropped;
    }

    /**
     * Cale aleatoare, împărțită pe două niveluri.
     *
     * Numele nu conține nimic din ce a trimis utilizatorul, iar împărțirea evită
     * un singur director cu zeci de mii de fișiere.
     */
    private function generatePath(): string
    {
        $name = Str::lower(Str::random(32));

        return 'avatars/'.substr($name, 0, 2).'/'.substr($name, 2);
    }

    private function deleteFiles(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        foreach (self::SIZES as $size) {
            Storage::disk(self::DISK)->delete("{$path}-{$size}.webp");
        }
    }
}
