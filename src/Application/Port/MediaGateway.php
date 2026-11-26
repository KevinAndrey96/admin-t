<?php

declare(strict_types=1);

namespace WooPanel\Application\Port;

use WooPanel\Application\ImageUpload;

interface MediaGateway
{
    /** Stores the image in the WordPress media library and returns its attachment ID. */
    public function upload(ImageUpload $image): int;
}
