<?php

declare(strict_types=1);

namespace WooPanel\Domain;

/**
 * Amount as WooCommerce sends it (a decimal string) plus its ISO 4217 currency.
 */
final readonly class Money
{
    private const array ZERO_DECIMAL_CURRENCIES = ['COP', 'CLP', 'JPY', 'KRW', 'PYG', 'VND'];

    public string $amount;

    public function __construct(string $amount, public string $currency)
    {
        $normalized = trim($amount) === '' ? '0' : trim($amount);
        if (preg_match('/^-?\d+(\.\d+)?$/', $normalized) !== 1) {
            throw new \InvalidArgumentException(\sprintf('invalid amount "%s"', $amount));
        }
        $this->amount = $normalized;
    }

    public function format(): string
    {
        $decimals = \in_array($this->currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 0 : 2;

        return '$ ' . number_format((float) $this->amount, $decimals, ',', '.') . ' ' . $this->currency;
    }

    public function isZero(): bool
    {
        return (float) $this->amount === 0.0;
    }
}
