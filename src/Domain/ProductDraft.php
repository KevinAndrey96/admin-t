<?php

declare(strict_types=1);

namespace WooPanel\Domain;

/**
 * Editable fields of a simple product, validated before they reach the store.
 */
final readonly class ProductDraft
{
    public const int NAME_MAX = 200;
    public const int SKU_MAX = 100;
    public const int DESCRIPTION_MAX = 1000;

    public function __construct(
        public string $name = '',
        public string $sku = '',
        public string $regularPrice = '',
        public string $salePrice = '',
        public ProductStatus $status = ProductStatus::Draft,
        public bool $manageStock = false,
        public ?int $stockQuantity = null,
        public string $shortDescription = '',
    ) {}

    /**
     * Builds a draft from submitted form fields.
     *
     * @param array<array-key, mixed> $input
     *
     * @throws InvalidProductDraft when any field is invalid
     */
    public static function fromInput(array $input): self
    {
        $text = static fn(string $key): string => \is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $errors = [];

        $name = $text('name');
        if ($name === '') {
            $errors['name'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($name) > self::NAME_MAX) {
            $errors['name'] = \sprintf('El nombre admite máximo %d caracteres.', self::NAME_MAX);
        }

        $sku = $text('sku');
        if ($sku !== '' && preg_match('/^[A-Za-z0-9._-]{1,' . self::SKU_MAX . '}$/', $sku) !== 1) {
            $errors['sku'] = 'El SKU solo admite letras, números, punto, guion y guion bajo.';
        }

        $regularPrice = self::normalizePrice($text('regular_price'));
        if ($regularPrice === null || $regularPrice === '') {
            $errors['regular_price'] = 'Escribe un precio válido, por ejemplo 125000 o 125000,50.';
        }

        $salePrice = self::normalizePrice($text('sale_price'));
        if ($salePrice === null) {
            $errors['sale_price'] = 'Escribe un precio de oferta válido o déjalo vacío.';
        } elseif ($salePrice !== '' && \is_string($regularPrice) && $regularPrice !== '' && (float) $salePrice >= (float) $regularPrice) {
            $errors['sale_price'] = 'El precio de oferta debe ser menor que el precio normal.';
        }

        $status = ProductStatus::tryFrom($text('status'));
        if ($status === null) {
            $errors['status'] = 'Elige un estado de publicación válido.';
        }

        $manageStock = $text('manage_stock') === '1';
        $stockQuantity = null;
        if ($manageStock) {
            $rawStock = $text('stock_quantity');
            if (preg_match('/^\d{1,7}$/', $rawStock) === 1) {
                $stockQuantity = (int) $rawStock;
            } else {
                $errors['stock_quantity'] = 'Las existencias deben ser un número entero mayor o igual a cero.';
            }
        }

        $description = $text('short_description');
        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $errors['short_description'] = \sprintf('La descripción admite máximo %d caracteres.', self::DESCRIPTION_MAX);
        }

        $draft = new self(
            $name,
            $sku,
            $regularPrice ?? $text('regular_price'),
            $salePrice ?? $text('sale_price'),
            $status ?? ProductStatus::Draft,
            $manageStock,
            $stockQuantity,
            $description,
        );
        if ($errors !== []) {
            throw new InvalidProductDraft($errors, $draft);
        }

        return $draft;
    }

    /**
     * Body for the WooCommerce products endpoint.
     *
     * @return array<string, scalar|null>
     */
    public function toPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'type' => 'simple',
            'sku' => $this->sku,
            'regular_price' => $this->regularPrice,
            'sale_price' => $this->salePrice,
            'status' => $this->status->value,
            'manage_stock' => $this->manageStock,
            'short_description' => $this->shortDescription,
        ];
        if ($this->manageStock) {
            $payload['stock_quantity'] = $this->stockQuantity;
        }

        return $payload;
    }

    /**
     * Accepts "125000", "125.000", "125000,5" or "125000.50" and returns WooCommerce's dotted decimal.
     * Returns an empty string for an empty field and null when the value is not a price.
     */
    private static function normalizePrice(string $raw): ?string
    {
        $value = str_replace([' ', '$'], '', $raw);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value) === 1) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        if (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
