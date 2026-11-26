<?php
/**
 * @var \WooPanel\View\View $this
 * @var string $content
 * @var \WooPanel\View\Layout $layout
 */
$nav = ['dashboard' => ['/', 'Resumen', 'grid'], 'orders' => ['/pedidos', 'Pedidos', 'receipt'], 'products' => ['/productos', 'Productos', 'box']];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <title><?= $this->e($layout->title) ?> · <?= $this->e($layout->storeName) ?></title>
    <link rel="icon" href="<?= $this->asset('/assets/icon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $this->asset('/assets/app.css') ?>">
    <script type="module" src="<?= $this->asset('/assets/app.js') ?>"></script>
</head>
<body class="<?= $layout->user === null ? 'is-guest' : 'is-app' ?>">
<svg class="sprite" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <symbol id="i-grid" viewBox="0 0 24 24"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6M9 16h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-box" viewBox="0 0 24 24"><path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-store" viewBox="0 0 24 24"><path d="M4 9h16l-1.5-5h-13zM5 9v11h14V9M10 20v-6h4v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-next" viewBox="0 0 24 24"><path d="m10 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3.5" y="4.5" width="17" height="15" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="10" r="1.75" fill="currentColor"/><path d="m4 18 5-5 3 3 3-3 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
</svg>

<?php if ($layout->user !== null): ?>
<header class="topbar">
    <div class="topbar__inner">
        <a class="brand" href="/">
            <img src="<?= $this->asset('/assets/icon.svg') ?>" alt="" width="28" height="28">
            <span><?= $this->e($layout->storeName) ?></span>
        </a>
        <nav class="nav" aria-label="Secciones">
            <?php foreach ($nav as $section => [$href, $label, $icon]): ?>
                <a href="<?= $href ?>"<?= $layout->section === $section ? ' aria-current="page"' : '' ?>>
                    <svg class="icon" aria-hidden="true"><use href="#i-<?= $icon ?>"/></svg><?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="account">
            <a class="account__store" href="<?= $this->e($layout->storePublicUrl) ?>" target="_blank" rel="noopener" title="Abrir la tienda">
                <svg class="icon" aria-hidden="true"><use href="#i-store"/></svg><span class="sr-only">Abrir la tienda</span>
            </a>
            <span class="account__name"><?= $this->e($layout->user->displayName) ?></span>
            <form method="post" action="/salir">
                <input type="hidden" name="_token" value="<?= $this->e($layout->csrfToken) ?>">
                <button class="button button--ghost" type="submit" title="Cerrar sesión">
                    <svg class="icon" aria-hidden="true"><use href="#i-logout"/></svg><span class="sr-only">Cerrar sesión</span>
                </button>
            </form>
        </div>
    </div>
</header>
<?php endif; ?>

<main class="main" id="contenido">
    <?php foreach ($layout->flashes as $flash): ?>
        <div class="flash flash--<?= $this->e($flash['tone']) ?>" role="<?= $flash['tone'] === 'error' ? 'alert' : 'status' ?>">
            <p><?= $this->e($flash['message']) ?></p>
            <button class="flash__close" type="button" data-dismiss aria-label="Cerrar aviso"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button>
        </div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
