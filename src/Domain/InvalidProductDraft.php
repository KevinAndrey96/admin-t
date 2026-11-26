<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final class InvalidProductDraft extends \DomainException
{
    /**
     * @param array<string, string> $errors field name => message shown next to the field
     */
    public function __construct(public readonly array $errors, public readonly ProductDraft $draft)
    {
        parent::__construct('invalid product draft: ' . implode(', ', array_keys($errors)));
    }
}
