<?php
declare(strict_types=1);

namespace App\Helpers;

class Format
{
    /**
     * Format monetary amounts cleanly across all PHP 8+ versions
     */
    public static function currency(float $amount, string $symbol = '$'): string
    {
        return $symbol . ' ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Format datetime to human-readable string
     */
    public static function datetime(?string $datetime): string
    {
        if (empty($datetime)) {
            return 'N/A';
        }
        $time = strtotime($datetime);
        if ($time === false) {
            return (string)$datetime;
        }
        return date('d/m/Y H:i', $time);
    }

    /**
     * Render a colored status badge for WooCommerce order status
     */
    public static function statusBadge(string $status): string
    {
        $status = strtolower(trim($status));

        $badges = [
            'wc-completed'  => ['label' => 'Completado', 'class' => 'badge-success', 'icon' => 'fa-check-circle'],
            'wc-processing' => ['label' => 'En Proceso', 'class' => 'badge-primary', 'icon' => 'fa-refresh'],
            'wc-on-hold'    => ['label' => 'En Espera', 'class' => 'badge-warning text-dark', 'icon' => 'fa-clock-o'],
            'wc-pending'    => ['label' => 'Pendiente', 'class' => 'badge-secondary', 'icon' => 'fa-hourglass-start'],
            'wc-cancelled'  => ['label' => 'Cancelado', 'class' => 'badge-dark', 'icon' => 'fa-ban'],
            'wc-refunded'   => ['label' => 'Reembolsado', 'class' => 'badge-info', 'icon' => 'fa-undo'],
            'wc-failed'     => ['label' => 'Fallido', 'class' => 'badge-danger', 'icon' => 'fa-times-circle'],
            'trash'         => ['label' => 'Papelera', 'class' => 'badge-danger', 'icon' => 'fa-trash'],
        ];

        if (isset($badges[$status])) {
            $item = $badges[$status];
            return sprintf(
                '<span class="badge %s p-2"><i class="fa %s mr-1"></i> %s</span>',
                $item['class'],
                $item['icon'],
                htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
            );
        }

        $cleanLabel = ucfirst(str_replace(['wc-', '-'], ['', ' '], $status));
        return sprintf(
            '<span class="badge badge-light p-2">%s</span>',
            htmlspecialchars($cleanLabel, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Return human friendly translation of order status
     */
    public static function statusLabel(string $status): string
    {
        $status = strtolower(trim($status));
        $labels = [
            'wc-completed'  => 'Completado',
            'wc-processing' => 'En Proceso',
            'wc-on-hold'    => 'En Espera',
            'wc-pending'    => 'Pendiente de Pago',
            'wc-cancelled'  => 'Cancelado',
            'wc-refunded'   => 'Reembolsado',
            'wc-failed'     => 'Fallido',
            'trash'         => 'En Papelera',
        ];

        return $labels[$status] ?? ucfirst(str_replace(['wc-', '-'], ['', ' '], $status));
    }
}
