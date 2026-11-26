<?php
/**
 * @var \WooPanel\View\View $this
 * @var \WooPanel\Domain\Page<\WooPanel\Domain\Order> $page
 * @var \WooPanel\Application\OrderQuery $query
 * @var list<\WooPanel\Domain\OrderStatus> $statuses
 */
$params = ['estado' => $query->status->value ?? '', 'buscar' => $query->search];
?>
<header class="page-head">
    <div>
        <h1>Pedidos</h1>
        <p class="muted"><?= $page->total ?> <?= $page->total === 1 ? 'pedido' : 'pedidos' ?><?= $query->status !== null ? ' en «' . $query->status->label() . '»' : '' ?></p>
    </div>
</header>

<form class="filters" method="get" action="/pedidos" role="search">
    <label class="field field--grow">
        <span class="sr-only">Buscar pedidos</span>
        <input type="search" name="buscar" value="<?= $this->e($query->search) ?>" placeholder="Número, nombre, correo o teléfono" maxlength="100">
    </label>
    <label class="field">
        <span class="sr-only">Estado</span>
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status->value ?>"<?= $query->status === $status ? ' selected' : '' ?>><?= $status->label() ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button" type="submit"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>Filtrar</button>
    <?php if ($query->search !== '' || $query->status !== null): ?>
        <a class="link" href="/pedidos">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($page->items === []): ?>
    <p class="card empty">No encontramos pedidos con esos filtros.</p>
<?php else: ?>
    <div class="card table-wrap">
        <table class="table table--stack">
            <thead>
            <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Fecha</th>
                <th scope="col">Cliente</th>
                <th scope="col">Estado</th>
                <th scope="col" class="num">Artículos</th>
                <th scope="col" class="num">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($page->items as $order): ?>
                <tr>
                    <td data-label="Pedido"><a class="link" href="/pedidos/<?= $order->id ?>"><strong>#<?= $this->e($order->number) ?></strong></a></td>
                    <td data-label="Fecha"><?= $this->date($order->createdAt) ?></td>
                    <td data-label="Cliente">
                        <?= $this->e($order->customerName()) ?>
                        <?php if ($order->email !== ''): ?><small class="muted block"><?= $this->e($order->email) ?></small><?php endif; ?>
                    </td>
                    <td data-label="Estado"><span class="badge badge--<?= $order->status->tone() ?>"><?= $order->status->label() ?></span></td>
                    <td class="num" data-label="Artículos"><?= $order->itemCount() ?></td>
                    <td class="num" data-label="Total"><strong><?= $this->money($order->total) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->partial('partials/pagination', ['page' => $page, 'path' => '/pedidos', 'params' => $params]) ?>
<?php endif; ?>
