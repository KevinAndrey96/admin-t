<?php

declare(strict_types=1);

namespace WooPanel\Domain;

enum StockStatus: string
{
    case InStock = 'instock';
    case OutOfStock = 'outofstock';
    case OnBackorder = 'onbackorder';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'Disponible',
            self::OutOfStock => 'Agotado',
            self::OnBackorder => 'Bajo pedido',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InStock => 'success',
            self::OutOfStock => 'danger',
            self::OnBackorder => 'warning',
        };
    }
}
