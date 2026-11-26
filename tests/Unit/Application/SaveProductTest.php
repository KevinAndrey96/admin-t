<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\ImageUpload;
use WooPanel\Application\SaveProduct;
use WooPanel\Domain\ProductDraft;
use WooPanel\Tests\Support\Fixtures;
use WooPanel\Tests\Support\InMemoryStore;

final class SaveProductTest extends TestCase
{
    public function testCreatesWithoutImage(): void
    {
        $memory = new InMemoryStore();

        (new SaveProduct($memory->products, $memory->media))(null, new ProductDraft('Taza'), null);

        self::assertSame([], $memory->media->uploads);
        self::assertCount(1, $memory->products->saved);
        self::assertNull($memory->products->saved[0][0]);
        self::assertNull($memory->products->saved[0][2]);
    }

    public function testUploadsTheImageBeforeUpdating(): void
    {
        $memory = new InMemoryStore();
        $path = (string) tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, Fixtures::pngBytes());

        try {
            (new SaveProduct($memory->products, $memory->media))(77, new ProductDraft('Taza'), ImageUpload::fromFile($path, 'taza.png'));
        } finally {
            unlink($path);
        }

        self::assertCount(1, $memory->media->uploads);
        self::assertSame([77, 501], [$memory->products->saved[0][0], $memory->products->saved[0][2]]);
    }

    public function testDoesNotSaveWhenTheUploadFails(): void
    {
        $memory = new InMemoryStore();
        $memory->failure = new StoreRejected('Tipo de archivo no permitido.', 400, 'rest_upload_sideload_error');
        $path = (string) tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, Fixtures::pngBytes());

        try {
            (new SaveProduct($memory->products, $memory->media))(null, new ProductDraft('Taza'), ImageUpload::fromFile($path, 'taza.png'));
            self::fail('expected StoreRejected');
        } catch (StoreRejected) {
            self::assertSame([], $memory->products->saved);
        } finally {
            unlink($path);
        }
    }
}
