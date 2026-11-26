<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WooPanel\Domain\InvalidProductDraft;
use WooPanel\Domain\ProductDraft;
use WooPanel\Domain\ProductStatus;

final class ProductDraftTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function validInput(): array
    {
        return [
            'name' => '  Taza de cerámica  ',
            'sku' => 'TAZA-001',
            'regular_price' => '35000',
            'sale_price' => '',
            'status' => 'publish',
            'manage_stock' => '1',
            'stock_quantity' => '24',
            'short_description' => 'Hecha a mano',
        ];
    }

    public function testBuildsADraftFromValidInput(): void
    {
        $draft = ProductDraft::fromInput(self::validInput());

        self::assertSame('Taza de cerámica', $draft->name);
        self::assertSame('35000', $draft->regularPrice);
        self::assertSame(ProductStatus::Publish, $draft->status);
        self::assertTrue($draft->manageStock);
        self::assertSame(24, $draft->stockQuantity);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function prices(): iterable
    {
        yield 'plain integer' => ['125000', '125000'];
        yield 'thousands with dots' => ['125.000', '125000'];
        yield 'decimal comma' => ['125000,5', '125000.5'];
        yield 'decimal dot' => ['19.99', '19.99'];
        yield 'currency sign and spaces' => ['$ 59 000', '59000'];
    }

    #[DataProvider('prices')]
    public function testNormalizesPrices(string $raw, string $expected): void
    {
        $draft = ProductDraft::fromInput(['regular_price' => $raw] + self::validInput());

        self::assertSame($expected, $draft->regularPrice);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'missing name' => [['name' => '   '], 'name'];
        yield 'name too long' => [['name' => str_repeat('a', ProductDraft::NAME_MAX + 1)], 'name'];
        yield 'sku with spaces' => [['sku' => 'TAZA 001'], 'sku'];
        yield 'missing price' => [['regular_price' => ''], 'regular_price'];
        yield 'price with letters' => [['regular_price' => '35mil'], 'regular_price'];
        yield 'negative price' => [['regular_price' => '-5'], 'regular_price'];
        yield 'sale not lower' => [['sale_price' => '35000'], 'sale_price'];
        yield 'unknown status' => [['status' => 'trash'], 'status'];
        yield 'stock not a number' => [['stock_quantity' => 'diez'], 'stock_quantity'];
        yield 'negative stock' => [['stock_quantity' => '-1'], 'stock_quantity'];
        yield 'description too long' => [['short_description' => str_repeat('a', ProductDraft::DESCRIPTION_MAX + 1)], 'short_description'];
        yield 'array instead of text' => [['name' => ['x']], 'name'];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidFields(array $override, string $field): void
    {
        try {
            ProductDraft::fromInput($override + self::validInput());
            self::fail('expected InvalidProductDraft');
        } catch (InvalidProductDraft $e) {
            self::assertArrayHasKey($field, $e->errors);
            self::assertCount(1, $e->errors);
        }
    }

    public function testKeepsWhatWasTypedWhenInvalid(): void
    {
        try {
            ProductDraft::fromInput(['regular_price' => 'gratis'] + self::validInput());
            self::fail('expected InvalidProductDraft');
        } catch (InvalidProductDraft $e) {
            self::assertSame('Taza de cerámica', $e->draft->name);
            self::assertSame('gratis', $e->draft->regularPrice);
        }
    }

    public function testIgnoresStockWhenNotManaged(): void
    {
        $draft = ProductDraft::fromInput(['manage_stock' => '', 'stock_quantity' => 'basura'] + self::validInput());

        self::assertFalse($draft->manageStock);
        self::assertNull($draft->stockQuantity);
        self::assertArrayNotHasKey('stock_quantity', $draft->toPayload());
    }

    public function testPayloadMatchesTheWooCommerceSchema(): void
    {
        $payload = ProductDraft::fromInput(['sale_price' => '29.900'] + self::validInput())->toPayload();

        self::assertSame([
            'name' => 'Taza de cerámica',
            'type' => 'simple',
            'sku' => 'TAZA-001',
            'regular_price' => '35000',
            'sale_price' => '29900',
            'status' => 'publish',
            'manage_stock' => true,
            'short_description' => 'Hecha a mano',
            'stock_quantity' => 24,
        ], $payload);
    }
}
