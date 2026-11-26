<?php

declare(strict_types=1);

namespace WooPanel\Domain;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case OnHold = 'on-hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Draft = 'checkout-draft';

    /**
     * Statuses a shop manager can set by hand. Drafts only exist while a checkout is open.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Pending, self::Processing, self::OnHold, self::Completed, self::Cancelled, self::Refunded, self::Failed];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de pago',
            self::Processing => 'Procesando',
            self::OnHold => 'En espera',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
            self::Failed => 'Fallido',
            self::Draft => 'Borrador',
        };
    }

    /** Visual tone of the status badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::OnHold => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Cancelled, self::Refunded, self::Draft => 'muted',
        };
    }
}
