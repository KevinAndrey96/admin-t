<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WooPanel\Application\ImageUpload;
use WooPanel\Application\InvalidImage;
use WooPanel\Tests\Support\Fixtures;

final class ImageUploadTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testAcceptsARealImageAndCleansItsName(): void
    {
        $image = ImageUpload::fromFile($this->file(Fixtures::pngBytes()), '../../Foto Bolso (1).PNG');

        self::assertSame('image/png', $image->mimeType);
        self::assertSame('foto-bolso-1.png', $image->filename);
        self::assertSame(Fixtures::pngBytes(), $image->contents());
    }

    public function testUsesTheDetectedTypeNotTheExtension(): void
    {
        $image = ImageUpload::fromFile($this->file(Fixtures::pngBytes()), 'foto.jpg');

        self::assertSame('foto.png', $image->filename);
    }

    public function testRejectsFilesThatAreNotImages(): void
    {
        $this->expectException(InvalidImage::class);
        ImageUpload::fromFile($this->file('<?php echo "hola";'), 'foto.png');
    }

    public function testRejectsEmptyFiles(): void
    {
        $this->expectException(InvalidImage::class);
        ImageUpload::fromFile($this->file(''), 'foto.png');
    }

    public function testRejectsFilesOverTheLimit(): void
    {
        $this->expectException(InvalidImage::class);
        $this->expectExceptionMessage('5 MB');
        ImageUpload::fromFile($this->file(Fixtures::pngBytes() . str_repeat("\0", ImageUpload::MAX_BYTES)), 'grande.png');
    }

    private function file(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $contents);
        $this->files[] = $path;

        return $path;
    }
}
