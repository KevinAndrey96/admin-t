<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class Order
{
    /**
     * @param list<OrderItem> $items
     */
    public function __construct(
        public int $id,
        public string $number,
        public OrderStatus $status,
        public \DateTimeImmutable $createdAt,
        public Money $total,
        public Money $shippingTotal,
        public Money $discountTotal,
        public Address $billing,
        public Address $shipping,
        public string $email,
        public string $phone,
        public string $paymentMethod,
        public string $customerNote,
        public array $items,
    ) {}

    public function customerName(): string
    {
        $name = $this->billing->fullName();

        return $name !== '' ? $name : 'Cliente invitado';
    }

    public function itemCount(): int
    {
        return array_sum(array_map(static fn(OrderItem $item): int => $item->quantity, $this->items));
    }
}
