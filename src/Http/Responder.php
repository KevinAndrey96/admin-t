<?php

declare(strict_types=1);

namespace WooPanel\Http;

use WooPanel\Domain\StoreUser;
use WooPanel\View\Layout;
use WooPanel\View\View;

/**
 * Builds the responses of one request with the layout data it needs.
 */
final readonly class Responder
{
    public function __construct(
        private View $view,
        private SessionState $state,
        private AppConfig $config,
        private string $section,
        private ?StoreUser $user = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function page(string $template, array $data, string $title, int $status = 200): Response
    {
        $layout = new Layout(
            $title,
            $this->section,
            $this->state->csrfToken(),
            $this->state->pullFlashes(),
            $this->config->storeName,
            $this->config->storePublicUrl,
            $this->user,
        );

        return Response::html($this->view->page($template, ['csrf' => $layout->csrfToken, ...$data], $layout), $status);
    }

    public function redirect(string $location, string $tone = '', string $message = ''): Response
    {
        if ($message !== '') {
            $this->state->flash($tone, $message);
        }

        return Response::redirect($location);
    }

    public function error(int $status, string $title, string $message): Response
    {
        return $this->page('error', ['status' => $status, 'heading' => $title, 'message' => $message], $title, $status);
    }
}
