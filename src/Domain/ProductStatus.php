<?php

declare(strict_types=1);

namespace WooPanel\Domain;

enum ProductStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Publish => 'Publicado',
            self::Draft => 'Borrador',
            self::Pending => 'Pendiente de revisión',
            self::Private => 'Privado',
        };
    }
}
