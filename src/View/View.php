<?php

declare(strict_types=1);

namespace WooPanel\View;

use WooPanel\Domain\Money;

/**
 * Renders plain PHP templates. Templates escape every value with e().
 */
final readonly class View
{
    public function __construct(
        private string $templatesDir,
        private string $publicDir,
        private \DateTimeZone $timezone,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function page(string $template, array $data, Layout $layout): string
    {
        return $this->render('layout', ['content' => $this->render($template, $data), 'layout' => $layout]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data): string
    {
        return $this->render($template, $data);
    }

    public function e(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Public URL of a static file with a content hash, so long browser caches never serve stale code. */
    public function asset(string $path): string
    {
        $hash = @md5_file($this->publicDir . $path);

        return $hash === false ? $path : $path . '?v=' . substr($hash, 0, 10);
    }

    public function money(Money $money): string
    {
        return $this->e($money->format());
    }

    /** Product prices come without currency; the panel shows them in the store's own format. */
    public function price(string $amount): string
    {
        if ($amount === '' || !is_numeric($amount)) {
            return 'Sin precio';
        }
        $value = (float) $amount;
        $decimals = floor($value) === $value ? 0 : 2;

        return $this->e('$ ' . number_format($value, $decimals, ',', '.'));
    }

    public function date(\DateTimeImmutable $date, bool $withTime = true): string
    {
        return $this->e($date->setTimezone($this->timezone)->format($withTime ? 'd/m/Y H:i' : 'd/m/Y'));
    }

    /**
     * @param array<string, scalar|null> $query empty values and the first page are left out
     */
    public function url(string $path, array $query = []): string
    {
        $query = array_filter(
            $query,
            static fn(mixed $value, string $key): bool => $value !== null && $value !== '' && !($key === 'pagina' && $value === 1),
            \ARRAY_FILTER_USE_BOTH,
        );
        $url = $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', \PHP_QUERY_RFC3986);

        return $this->e($url);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $template, array $data): string
    {
        $file = $this->templatesDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \LogicException(\sprintf('template "%s" not found', $template));
        }

        ob_start();
        try {
            (function (string $__file, array $__data): void {
                extract($__data, \EXTR_SKIP);
                require $__file;
            })->call($this, $file, $data);
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return (string) ob_get_clean();
    }
}
