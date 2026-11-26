<?php

declare(strict_types=1);

namespace WooPanel\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WooPanel\Domain\Address;
use WooPanel\Domain\Credentials;
use WooPanel\Domain\Money;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Page;
use WooPanel\Domain\StoreSummary;
use WooPanel\Tests\Support\Fixtures;

final class ValueObjectsTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function amounts(): iterable
    {
        yield 'pesos without decimals' => ['1250000', 'COP', '$ 1.250.000 COP'];
        yield 'pesos with cents are rounded' => ['99999.6', 'COP', '$ 100.000 COP'];
        yield 'dollars keep two decimals' => ['19.9', 'USD', '$ 19,90 USD'];
        yield 'empty means zero' => ['', 'COP', '$ 0 COP'];
    }

    #[DataProvider('amounts')]
    public function testMoneyFormatsLikeTheStore(string $amount, string $currency, string $expected): void
    {
        self::assertSame($expected, (new Money($amount, $currency))->format());
    }

    public function testMoneyRejectsGarbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money('12,5', 'COP');
    }

    public function testDraftsAreNotAssignable(): void
    {
        self::assertNotContains(OrderStatus::Draft, OrderStatus::assignable());
        self::assertCount(\count(OrderStatus::cases()) - 1, OrderStatus::assignable());
        self::assertSame('En espera', OrderStatus::OnHold->label());
    }

    public function testAddressSkipsEmptyLines(): void
    {
        $address = new Address('Laura', 'Gómez', '', 'Calle 45 # 12-30', '', 'Bogotá', 'DC', '110111', 'CO');

        self::assertSame(['Laura Gómez', 'Calle 45 # 12-30', 'Bogotá, DC 110111', 'CO'], $address->lines());
        self::assertTrue((new Address())->isEmpty());
    }

    public function testOrderFallsBackToGuestName(): void
    {
        $order = Fixtures::order();

        self::assertSame('Laura Gómez', $order->customerName());
        self::assertSame(2, $order->itemCount());
    }

    public function testCredentialsStripTheSpacesWordPressShows(): void
    {
        $credentials = new Credentials(' gestora ', 'abcd efgh ijkl mnop');

        self::assertSame('gestora', $credentials->username);
        self::assertSame('abcdefghijklmnop', $credentials->applicationPassword);
        self::assertStringNotContainsString('abcd', print_r($credentials, true));
    }

    public function testCredentialsRequireBothParts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Credentials('gestora', '   ');
    }

    public function testPageKnowsItsNeighbours(): void
    {
        $page = new Page([], 2, 45, 3);

        self::assertTrue($page->hasPrevious());
        self::assertTrue($page->hasNext());
        self::assertFalse((new Page([], 3, 45, 3))->hasNext());
    }

    public function testSummaryCountsOrdersToAttend(): void
    {
        $summary = new StoreSummary(new Money('0', 'COP'), 0, ['processing' => 3, 'on-hold' => 2, 'pending' => 9], 0, []);

        self::assertSame(5, $summary->ordersToAttend());
        self::assertSame(0, $summary->count(OrderStatus::Refunded));
    }
}
