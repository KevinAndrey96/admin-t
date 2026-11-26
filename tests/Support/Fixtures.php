<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Domain\Address;
use WooPanel\Domain\Money;
use WooPanel\Domain\Order;
use WooPanel\Domain\OrderItem;
use WooPanel\Domain\OrderStatus;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductStatus;
use WooPanel\Domain\StockStatus;

final class Fixtures
{
    /**
     * Shape of GET /wc/v3/orders/{id}, trimmed to the fields the panel reads.
     *
     * @return array<string, mixed>
     */
    public static function orderPayload(int $id = 1042, string $status = 'processing'): array
    {
        return [
            'id' => $id,
            'number' => (string) $id,
            'status' => $status,
            'currency' => 'COP',
            'date_created' => '2026-11-20T10:15:00',
            'date_created_gmt' => '2026-11-20T15:15:00',
            'total' => '112000',
            'shipping_total' => '12000',
            'discount_total' => '0',
            'payment_method_title' => 'Transferencia bancaria',
            'customer_note' => 'Dejar en portería &amp; avisar',
            'billing' => [
                'first_name' => 'Laura', 'last_name' => 'Gómez', 'company' => '', 'address_1' => 'Calle 45 # 12-30',
                'address_2' => '', 'city' => 'Bogotá', 'state' => 'DC', 'postcode' => '', 'country' => 'CO',
                'email' => 'laura@example.com', 'phone' => '300 123 4567',
            ],
            'shipping' => [
                'first_name' => 'Laura', 'last_name' => 'Gómez', 'company' => '', 'address_1' => 'Calle 45 # 12-30',
                'address_2' => '', 'city' => 'Bogotá', 'state' => 'DC', 'postcode' => '', 'country' => 'CO',
            ],
            'line_items' => [
                ['id' => 1, 'name' => 'Taza de cer&aacute;mica', 'sku' => 'TAZA-001', 'quantity' => 2, 'total' => '70000'],
                ['id' => 2, 'name' => 'Café Huila', 'sku' => '', 'quantity' => 1, 'total' => '30000'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function productPayload(int $id = 77): array
    {
        return [
            'id' => $id,
            'name' => 'Bolso de lona &#8211; estampado',
            'sku' => 'BOLSO-002',
            'status' => 'publish',
            'permalink' => 'https://tienda.example/producto/bolso',
            'regular_price' => '59000',
            'sale_price' => '',
            'manage_stock' => true,
            'stock_quantity' => 12,
            'stock_status' => 'instock',
            'short_description' => '<p>Lona gruesa, <strong>hecho a mano</strong>.</p>',
            'images' => [['id' => 9, 'src' => 'https://tienda.example/wp-content/uploads/bolso.jpg']],
        ];
    }

    public static function order(int $id = 1042, OrderStatus $status = OrderStatus::Processing): Order
    {
        $address = new Address('Laura', 'Gómez', '', 'Calle 45 # 12-30', '', 'Bogotá', 'DC', '', 'CO');

        return new Order(
            $id,
            (string) $id,
            $status,
            new \DateTimeImmutable('2026-11-20T15:15:00Z'),
            new Money('112000', 'COP'),
            new Money('12000', 'COP'),
            new Money('0', 'COP'),
            $address,
            $address,
            'laura@example.com',
            '3001234567',
            'Transferencia bancaria',
            '',
            [new OrderItem('Taza de cerámica', 'TAZA-001', 2, new Money('70000', 'COP'))],
        );
    }

    public static function product(int $id = 77, StockStatus $stock = StockStatus::InStock): Product
    {
        return new Product(
            $id,
            'Bolso de lona',
            'BOLSO-002',
            ProductStatus::Publish,
            '59000',
            '',
            true,
            12,
            $stock,
            'Lona gruesa',
            null,
            'https://tienda.example/producto/bolso',
        );
    }

    /** Smallest valid PNG (1x1 transparent pixel). */
    public static function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', true);
    }
}
