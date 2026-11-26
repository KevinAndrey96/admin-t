<?php

declare(strict_types=1);

namespace WooPanel\Http;

final readonly class Request
{
    /**
     * @param array<array-key, mixed> $query
     * @param array<array-key, mixed> $body
     * @param array<array-key, mixed> $files
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public array $body = [],
        public array $files = [],
        public string $clientIp = '',
        public bool $secure = false,
    ) {}

    /**
     * @param array<array-key, mixed> $server
     * @param array<array-key, mixed> $query
     * @param array<array-key, mixed> $body
     * @param array<array-key, mixed> $files
     * @param bool $trustForwardedProto true only behind a reverse proxy that sets X-Forwarded-Proto itself
     */
    public static function fromGlobals(array $server, array $query, array $body, array $files, bool $trustForwardedProto): self
    {
        $uri = \is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/';
        $path = parse_url($uri, \PHP_URL_PATH);
        $method = \is_string($server['REQUEST_METHOD'] ?? null) ? strtoupper($server['REQUEST_METHOD']) : 'GET';
        $https = $server['HTTPS'] ?? '';
        $forwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';

        return new self(
            $method,
            \is_string($path) && $path !== '' ? rawurldecode($path) : '/',
            $query,
            $body,
            self::onlyUploadedFiles($files),
            \is_string($server['REMOTE_ADDR'] ?? null) ? $server['REMOTE_ADDR'] : '',
            (\is_string($https) && $https !== '' && $https !== 'off') || ($trustForwardedProto && $forwardedProto === 'https'),
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isHead(): bool
    {
        return $this->method === 'HEAD';
    }

    public function query(string $key): string
    {
        $value = $this->query[$key] ?? '';

        return \is_string($value) ? trim($value) : '';
    }

    public function pageNumber(): int
    {
        $page = $this->query('pagina');

        return ctype_digit($page) && $page !== '0' ? min((int) $page, 10_000) : 1;
    }

    public function input(string $key): string
    {
        $value = $this->body[$key] ?? '';

        return \is_string($value) ? $value : '';
    }

    /**
     * An uploaded file entry, or null when the field was left empty.
     *
     * @return array{tmp_name: string, name: string, error: int}|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!\is_array($file) || !\is_int($file['error'] ?? null) || $file['error'] === \UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return [
            'tmp_name' => \is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '',
            'name' => \is_string($file['name'] ?? null) ? $file['name'] : '',
            'error' => $file['error'],
        ];
    }

    /**
     * Drops entries whose temporary file was not created by this upload, so no code can be tricked into reading other paths.
     *
     * @param array<array-key, mixed> $files
     *
     * @return array<array-key, mixed>
     */
    private static function onlyUploadedFiles(array $files): array
    {
        return array_filter(
            $files,
            static fn(mixed $file): bool => \is_array($file)
                && (($file['error'] ?? null) !== \UPLOAD_ERR_OK || (\is_string($file['tmp_name'] ?? null) && is_uploaded_file($file['tmp_name']))),
        );
    }
}
