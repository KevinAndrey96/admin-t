<?php

declare(strict_types=1);

namespace WooPanel\Infrastructure\WooCommerce;

use WooPanel\Domain\Address;
use WooPanel\Domain\Money;
use WooPanel\Domain\Order;
use WooPanel\Domain\OrderItem;
use WooPanel\Domain\OrderNote;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductStatus;
use WooPanel\Domain\StockStatus;

/**
 * Translates WooCommerce REST v3 resources into domain objects.
 */
final class Mapper
{
    /**
     * Statuses added by plugins are shown as "on hold" so they still stand out as needing attention.
     *
     * @param array<array-key, mixed> $data
     */
    public static function order(array $data): Order
    {
        $currency = Payload::string($data, 'currency');
        $money = static fn(string $key, array $from = []): Money => new Money(Payload::string($from === [] ? $data : $from, $key), $currency);
        $billing = Payload::object($data, 'billing');

        return new Order(
            Payload::int($data, 'id'),
            Payload::string($data, 'number') !== '' ? Payload::string($data, 'number') : (string) Payload::int($data, 'id'),
            OrderStatus::tryFrom(Payload::string($data, 'status')) ?? OrderStatus::OnHold,
            Payload::utcDate($data, 'date_created'),
            $money('total'),
            $money('shipping_total'),
            $money('discount_total'),
            self::address($billing),
            self::address(Payload::object($data, 'shipping')),
            Payload::string($billing, 'email'),
            Payload::string($billing, 'phone'),
            Payload::text($data, 'payment_method_title'),
            Payload::text($data, 'customer_note'),
            array_map(
                static fn(array $item): OrderItem => new OrderItem(
                    Payload::text($item, 'name'),
                    Payload::string($item, 'sku'),
                    Payload::int($item, 'quantity'),
                    $money('total', $item),
                ),
                Payload::objects($data, 'line_items'),
            ),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function product(array $data): Product
    {
        $images = Payload::objects($data, 'images');
        $imageUrl = $images === [] ? '' : Payload::string($images[0], 'src');

        return new Product(
            Payload::int($data, 'id'),
            Payload::text($data, 'name'),
            Payload::string($data, 'sku'),
            ProductStatus::tryFrom(Payload::string($data, 'status')) ?? ProductStatus::Draft,
            Payload::string($data, 'regular_price'),
            Payload::string($data, 'sale_price'),
            Payload::bool($data, 'manage_stock'),
            Payload::nullableInt($data, 'stock_quantity'),
            StockStatus::tryFrom(Payload::string($data, 'stock_status')) ?? StockStatus::InStock,
            Payload::text($data, 'short_description'),
            $imageUrl !== '' ? $imageUrl : null,
            Payload::string($data, 'permalink'),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function note(array $data): OrderNote
    {
        return new OrderNote(
            Payload::int($data, 'id'),
            Payload::text($data, 'note'),
            Payload::text($data, 'author'),
            Payload::utcDate($data, 'date_created'),
            Payload::bool($data, 'customer_note'),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private static function address(array $data): Address
    {
        return new Address(
            Payload::text($data, 'first_name'),
            Payload::text($data, 'last_name'),
            Payload::text($data, 'company'),
            Payload::text($data, 'address_1'),
            Payload::text($data, 'address_2'),
            Payload::text($data, 'city'),
            Payload::text($data, 'state'),
            Payload::text($data, 'postcode'),
            Payload::text($data, 'country'),
        );
    }
}
