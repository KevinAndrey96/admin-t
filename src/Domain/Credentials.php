<?php

declare(strict_types=1);

namespace WooPanel\Domain;

/**
 * A WordPress username with one of its application passwords.
 */
final readonly class Credentials
{
    public string $username;
    public string $applicationPassword;

    public function __construct(string $username, #[\SensitiveParameter] string $applicationPassword)
    {
        $this->username = trim($username);
        $this->applicationPassword = preg_replace('/\s+/', '', $applicationPassword) ?? '';
        if ($this->username === '' || $this->applicationPassword === '') {
            throw new \InvalidArgumentException('username and application password are required');
        }
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['username' => $this->username, 'applicationPassword' => '***'];
    }
}
