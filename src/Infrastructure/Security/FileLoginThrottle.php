<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\Security;

use WooPanel\Http\LoginThrottle;

/**
 * Sliding window of failed logins per key, stored as small files so it works without Redis or APCu.
 * For several panel replicas, point the directory to shared storage.
 */
final readonly class FileLoginThrottle implements LoginThrottle
{
    public function __construct(
        private string $directory,
        private int $maxAttempts = 5,
        private int $windowSeconds = 900,
    ) {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(\sprintf('creating throttle directory %s', $this->directory));
        }
    }

    public function tooManyAttempts(string $key): bool
    {
        return \count($this->recent($this->read($key))) >= $this->maxAttempts;
    }

    public function recordFailure(string $key): void
    {
        $handle = fopen($this->path($key), 'c+');
        if ($handle === false) {
            throw new \RuntimeException('opening throttle file');
        }

        try {
            flock($handle, \LOCK_EX);
            $attempts = $this->recent(self::decode((string) stream_get_contents($handle)));
            $attempts[] = time();
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($attempts, \JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, \LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    public function clear(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @return list<int>
     */
    private function read(string $key): array
    {
        $path = $this->path($key);

        return is_file($path) ? self::decode((string) file_get_contents($path)) : [];
    }

    /**
     * @param list<int> $attempts
     *
     * @return list<int>
     */
    private function recent(array $attempts): array
    {
        $since = time() - $this->windowSeconds;

        return array_values(array_filter($attempts, static fn(int $at): bool => $at > $since));
    }

    /**
     * @return list<int>
     */
    private static function decode(string $json): array
    {
        $data = json_decode($json, true);

        return \is_array($data) ? array_values(array_filter($data, \is_int(...))) : [];
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
