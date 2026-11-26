<?php
/**
 * @var \WooPanel\View\View $this
 * @var \WooPanel\Domain\StoreSummary $summary
 * @var list<\WooPanel\Domain\OrderStatus> $statuses
 */
use WooPanel\Domain\OrderStatus;

?>
<header class="page-head">
    <div>
        <h1>Resumen</h1>
        <p class="muted">Cómo va la tienda este mes.</p>
    </div>
</header>

<section class="kpis" aria-label="Indicadores del mes">
    <article class="kpi">
        <h2>Ventas del mes</h2>
        <p class="kpi__value"><?= $this->money($summary->salesThisMonth) ?></p>
        <p class="muted">Completados, procesando o en espera</p>
    </article>
    <article class="kpi">
        <h2>Pedidos del mes</h2>
        <p class="kpi__value"><?= $summary->ordersThisMonth ?></p>
        <p class="muted">Sin pendientes de pago, cancelados ni fallidos</p>
    </article>
    <a class="kpi kpi--link<?= $summary->ordersToAttend() > 0 ? ' kpi--attention' : '' ?>" href="<?= $this->url('/pedidos', ['estado' => OrderStatus::Processing->value]) ?>">
        <h2>Por atender</h2>
        <p class="kpi__value"><?= $summary->ordersToAttend() ?></p>
        <p class="muted">Procesando o en espera</p>
    </a>
    <a class="kpi kpi--link<?= $summary->outOfStockProducts > 0 ? ' kpi--attention' : '' ?>" href="<?= $this->url('/productos', ['existencias' => 'outofstock']) ?>">
        <h2>Productos agotados</h2>
        <p class="kpi__value"><?= $summary->outOfStockProducts ?></p>
        <p class="muted">Publicados y sin existencias</p>
    </a>
</section>

<div class="columns">
    <section class="card">
        <header class="card__head">
            <h2>Últimos pedidos</h2>
            <a class="link" href="/pedidos">Ver todos</a>
        </header>
        <?php if ($summary->recentOrders === []): ?>
            <p class="empty">Todavía no hay pedidos.</p>
        <?php else: ?>
            <ul class="rows">
                <?php foreach ($summary->recentOrders as $order): ?>
                    <li>
                        <a class="row-link" href="/pedidos/<?= $order->id ?>">
                            <span>
                                <strong>#<?= $this->e($order->number) ?></strong> <?= $this->e($order->customerName()) ?>
                                <small class="muted"><?= $this->date($order->createdAt) ?></small>
                            </span>
                            <span class="row-link__end">
                                <span class="badge badge--<?= $order->status->tone() ?>"><?= $order->status->label() ?></span>
                                <strong><?= $this->money($order->total) ?></strong>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <header class="card__head"><h2>Pedidos por estado</h2></header>
        <ul class="rows">
            <?php foreach ($statuses as $status): ?>
                <li>
                    <a class="row-link" href="<?= $this->url('/pedidos', ['estado' => $status->value]) ?>">
                        <span class="badge badge--<?= $status->tone() ?>"><?= $status->label() ?></span>
                        <strong><?= $summary->count($status) ?></strong>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
