<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\ImageUpload;
use WooPanel\Application\Port\MediaGateway;

final class InMemoryMedia implements MediaGateway
{
    /** @var list<ImageUpload> */
    public array $uploads = [];

    public function __construct(private readonly InMemoryStore $store) {}

    public function upload(ImageUpload $image): int
    {
        $this->store->failIfAsked();
        $this->uploads[] = $image;

        return 500 + \count($this->uploads);
    }
}
