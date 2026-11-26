<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure;

use WooPanel\Http\ErrorReporter;
use WooPanel\Http\Request;

/**
 * One JSON line per error on stderr, where Docker and most log collectors pick it up.
 */
final readonly class StderrErrorReporter implements ErrorReporter
{
    public function __construct(private string $stream = 'php://stderr') {}

    public function report(\Throwable $error, Request $request): void
    {
        $line = json_encode([
            'time' => gmdate('c'),
            'level' => 'error',
            'message' => $error->getMessage(),
            'error' => $error::class,
            'previous' => $error->getPrevious()?->getMessage(),
            'method' => $request->method,
            'path' => $request->path,
            'at' => $error->getFile() . ':' . $error->getLine(),
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);

        file_put_contents($this->stream, $line . \PHP_EOL, \FILE_APPEND);
    }
}
