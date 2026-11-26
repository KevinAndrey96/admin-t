<?php

declare(strict_types=1);

namespace WooPanel\Http;

use WooPanel\Domain\StoreUser;

/**
 * Typed view over the session: sign-in state, CSRF token and one-time flash messages.
 */
final readonly class SessionState
{
    private const string AUTH = 'auth';
    private const string CSRF = 'csrf';
    private const string FLASH = 'flash';

    public function __construct(private Session $session) {}

    public function csrfToken(): string
    {
        $token = $this->session->get(self::CSRF);
        if (!\is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::CSRF, $token);
        }

        return $token;
    }

    public function isValidCsrf(string $submitted): bool
    {
        $token = $this->session->get(self::CSRF);

        return \is_string($token) && $token !== '' && hash_equals($token, $submitted);
    }

    public function flash(string $tone, string $message): void
    {
        $this->session->set(self::FLASH, [...$this->flashes(), ['tone' => $tone, 'message' => $message]]);
    }

    /**
     * @return list<array{tone: string, message: string}>
     */
    public function pullFlashes(): array
    {
        $flashes = $this->flashes();
        $this->session->remove(self::FLASH);

        return $flashes;
    }

    public function signIn(StoreUser $user, string $sealedCredentials): void
    {
        $this->session->regenerate();
        $this->session->remove(self::CSRF);
        $this->session->set(self::AUTH, [
            'id' => $user->id,
            'name' => $user->displayName,
            'credentials' => $sealedCredentials,
            'since' => time(),
            'seen' => time(),
        ]);
    }

    public function auth(): ?AuthSession
    {
        $auth = $this->session->get(self::AUTH);
        if (!\is_array($auth)
            || !\is_int($auth['id'] ?? null)
            || !\is_string($auth['name'] ?? null)
            || !\is_string($auth['credentials'] ?? null)
            || !\is_int($auth['since'] ?? null)
            || !\is_int($auth['seen'] ?? null)) {
            return null;
        }

        return new AuthSession(new StoreUser($auth['id'], $auth['name']), $auth['credentials'], $auth['since'], $auth['seen']);
    }

    public function touch(): void
    {
        $auth = $this->session->get(self::AUTH);
        if (\is_array($auth)) {
            $auth['seen'] = time();
            $this->session->set(self::AUTH, $auth);
        }
    }

    public function signOut(): void
    {
        $this->session->clear();
    }

    /**
     * @return list<array{tone: string, message: string}>
     */
    private function flashes(): array
    {
        $stored = $this->session->get(self::FLASH);
        if (!\is_array($stored)) {
            return [];
        }

        $flashes = [];
        foreach ($stored as $flash) {
            if (\is_array($flash) && \is_string($flash['tone'] ?? null) && \is_string($flash['message'] ?? null)) {
                $flashes[] = ['tone' => $flash['tone'], 'message' => $flash['message']];
            }
        }

        return $flashes;
    }
}
