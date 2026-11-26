<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Application\ImageUpload;
use WooPanel\Application\Port\MediaGateway;

final readonly class WpMediaGateway implements MediaGateway
{
    public function __construct(private RestClient $client) {}

    public function upload(ImageUpload $image): int
    {
        $id = Payload::int($this->client->upload('wp/v2/media', $image->contents(), $image->mimeType, $image->filename)->object(), 'id');
        if ($id <= 0) {
            throw new StoreUnavailable('media upload: answer has no attachment id');
        }

        return $id;
    }
}
