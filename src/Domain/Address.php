<?php

declare(strict_types=1);

namespace WooPanel\Domain;

final readonly class Address
{
    public function __construct(
        public string $firstName = '',
        public string $lastName = '',
        public string $company = '',
        public string $line1 = '',
        public string $line2 = '',
        public string $city = '',
        public string $state = '',
        public string $postcode = '',
        public string $country = '',
    ) {}

    public function fullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Printable lines without empty parts.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        $locality = trim(implode(', ', array_filter([$this->city, $this->state], static fn(string $part): bool => $part !== '')) . ' ' . $this->postcode);

        return array_values(array_filter(
            [$this->fullName(), $this->company, $this->line1, $this->line2, $locality, $this->country],
            static fn(string $line): bool => $line !== '',
        ));
    }

    public function isEmpty(): bool
    {
        return $this->lines() === [];
    }
}
