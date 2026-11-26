<?php

declare(strict_types=1);

namespace WooPanel\View;

use WooPanel\Domain\StoreUser;

final readonly class Layout
{
    /**
     * @param list<array{tone: string, message: string}> $flashes
     */
    public function __construct(
        public string $title,
        public string $section,
        public string $csrfToken,
        public array $flashes,
        public string $storeName,
        public string $storePublicUrl,
        public ?StoreUser $user = null,
    ) {}
}
