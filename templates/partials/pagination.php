<?php
/**
 * @var \WooPanel\View\View $this
 * @var \WooPanel\Domain\Page<mixed> $page
 * @var string $path
 * @var array<string, string> $params
 */
if ($page->totalPages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Paginación">
    <?php if ($page->hasPrevious()): ?>
        <a class="button button--ghost" href="<?= $this->url($path, [...$params, 'pagina' => $page->page - 1]) ?>" rel="prev">
            <svg class="icon" aria-hidden="true"><use href="#i-back"/></svg>Anterior
        </a>
    <?php endif; ?>
    <span class="muted">Página <?= $page->page ?> de <?= $page->totalPages ?></span>
    <?php if ($page->hasNext()): ?>
        <a class="button button--ghost" href="<?= $this->url($path, [...$params, 'pagina' => $page->page + 1]) ?>" rel="next">
            Siguiente<svg class="icon" aria-hidden="true"><use href="#i-next"/></svg>
        </a>
    <?php endif; ?>
</nav>
