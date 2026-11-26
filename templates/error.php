<?php
/**
 * @var \WooPanel\View\View $this
 * @var int $status
 * @var string $heading
 * @var string $message
 */
?>
<section class="error-page">
    <p class="error-page__code"><?= $status ?></p>
    <h1><?= $this->e($heading) ?></h1>
    <p class="muted"><?= $this->e($message) ?></p>
    <a class="button button--primary" href="/">Ir al resumen</a>
</section>
