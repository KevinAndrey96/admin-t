<?php

declare(strict_types=1);

namespace WooPanel\Application;

/**
 * A product photo that already passed type and size checks.
 */
final readonly class ImageUpload
{
    public const int MAX_BYTES = 5 * 1024 * 1024;

    private const array EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private function __construct(
        public string $path,
        public string $mimeType,
        public string $filename,
    ) {}

    /**
     * @throws InvalidImage when the file is empty, too large or not a JPEG, PNG or WebP picture
     */
    public static function fromFile(string $path, string $originalName): self
    {
        $size = is_file($path) ? filesize($path) : false;
        if ($size === false || $size === 0) {
            throw new InvalidImage('La imagen llegó vacía. Vuelve a seleccionarla.');
        }
        if ($size > self::MAX_BYTES) {
            throw new InvalidImage('La imagen supera el máximo de 5 MB.');
        }

        $mimeType = (new \finfo(\FILEINFO_MIME_TYPE))->file($path);
        $extension = $mimeType === false ? null : (self::EXTENSIONS[$mimeType] ?? null);
        if ($mimeType === false || $extension === null || @getimagesize($path) === false) {
            throw new InvalidImage('Solo se aceptan imágenes JPG, PNG o WebP.');
        }

        return new self($path, $mimeType, self::safeFilename($originalName, $extension));
    }

    public function contents(): string
    {
        $contents = file_get_contents($this->path);
        if ($contents === false) {
            throw new \RuntimeException(\sprintf('reading upload %s', $this->path));
        }

        return $contents;
    }

    private static function safeFilename(string $originalName, string $extension): string
    {
        $base = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', pathinfo($originalName, \PATHINFO_FILENAME)));
        $base = trim(substr($base, 0, 60), '-');

        return ($base !== '' ? $base : 'producto') . '.' . $extension;
    }
}
