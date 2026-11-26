<?php
/**
 * @var \WooPanel\View\View $this
 * @var string $csrf
 * @var \WooPanel\Domain\Order $order
 * @var list<\WooPanel\Domain\OrderNote> $notes
 * @var list<\WooPanel\Domain\OrderStatus> $statuses
 * @var int $noteMax
 */
?>
<a class="back" href="/pedidos"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg>Pedidos</a>
<header class="page-head">
    <div>
        <h1>Pedido #<?= $this->e($order->number) ?></h1>
        <p class="muted">Creado el <?= $this->date($order->createdAt) ?><?= $order->paymentMethod !== '' ? ' · ' . $this->e($order->paymentMethod) : '' ?></p>
    </div>
    <span class="badge badge--lg badge--<?= $order->status->tone() ?>"><?= $order->status->label() ?></span>
</header>

<div class="columns columns--wide">
    <div class="stack">
        <section class="card">
            <header class="card__head"><h2>Artículos</h2></header>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th scope="col">Producto</th><th scope="col" class="num">Cantidad</th><th scope="col" class="num">Total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($order->items as $item): ?>
                        <tr>
                            <td><?= $this->e($item->name) ?><?php if ($item->sku !== ''): ?><small class="muted block">SKU <?= $this->e($item->sku) ?></small><?php endif; ?></td>
                            <td class="num"><?= $item->quantity ?></td>
                            <td class="num"><?= $this->money($item->total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <?php if (!$order->shippingTotal->isZero()): ?>
                        <tr><th scope="row" colspan="2">Envío</th><td class="num"><?= $this->money($order->shippingTotal) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!$order->discountTotal->isZero()): ?>
                        <tr><th scope="row" colspan="2">Descuento</th><td class="num">-<?= $this->money($order->discountTotal) ?></td></tr>
                    <?php endif; ?>
                    <tr class="total"><th scope="row" colspan="2">Total</th><td class="num"><?= $this->money($order->total) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="columns">
            <section class="card">
                <header class="card__head"><h2>Cliente y facturación</h2></header>
                <address class="address">
                    <?php foreach ($order->billing->lines() as $line): ?><span><?= $this->e($line) ?></span><?php endforeach; ?>
                    <?php if ($order->email !== ''): ?><a class="link" href="mailto:<?= $this->e($order->email) ?>"><?= $this->e($order->email) ?></a><?php endif; ?>
                    <?php if ($order->phone !== ''): ?><a class="link" href="tel:<?= $this->e(preg_replace('/[^\d+]/', '', $order->phone) ?? '') ?>"><?= $this->e($order->phone) ?></a><?php endif; ?>
                </address>
            </section>
            <section class="card">
                <header class="card__head"><h2>Envío</h2></header>
                <?php if ($order->shipping->isEmpty()): ?>
                    <p class="empty">Sin dirección de envío.</p>
                <?php else: ?>
                    <address class="address">
                        <?php foreach ($order->shipping->lines() as $line): ?><span><?= $this->e($line) ?></span><?php endforeach; ?>
                    </address>
                <?php endif; ?>
            </section>
        </div>

        <?php if ($order->customerNote !== ''): ?>
            <section class="card">
                <header class="card__head"><h2>Nota del cliente</h2></header>
                <p class="prewrap"><?= $this->e($order->customerNote) ?></p>
            </section>
        <?php endif; ?>
    </div>

    <aside class="stack">
        <section class="card">
            <header class="card__head"><h2>Cambiar estado</h2></header>
            <form method="post" action="/pedidos/<?= $order->id ?>/estado" class="stack" data-busy>
                <input type="hidden" name="_token" value="<?= $this->e($csrf) ?>">
                <label class="field">
                    <span>Nuevo estado</span>
                    <select name="estado">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status->value ?>"<?= $order->status === $status ? ' selected' : '' ?>><?= $status->label() ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <p class="hint">WooCommerce envía al cliente los correos que tenga activos para el nuevo estado.</p>
                <button class="button button--primary" type="submit">Guardar estado</button>
            </form>
        </section>

        <section class="card" id="notas">
            <header class="card__head"><h2>Notas</h2></header>
            <form method="post" action="/pedidos/<?= $order->id ?>/notas" class="stack" data-busy>
                <input type="hidden" name="_token" value="<?= $this->e($csrf) ?>">
                <label class="field">
                    <span>Nueva nota</span>
                    <textarea name="nota" rows="3" maxlength="<?= $noteMax ?>" required></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" name="para_cliente" value="1">
                    <span>Enviar al cliente por correo</span>
                </label>
                <button class="button" type="submit">Agregar nota</button>
            </form>
            <?php if ($notes === []): ?>
                <p class="empty">Sin notas todavía.</p>
            <?php else: ?>
                <ol class="notes">
                    <?php foreach ($notes as $note): ?>
                        <li class="note<?= $note->visibleToCustomer ? ' note--customer' : '' ?>">
                            <p class="prewrap"><?= $this->e($note->text) ?></p>
                            <small class="muted">
                                <?= $this->date($note->createdAt) ?><?= $note->author !== '' ? ' · ' . $this->e($note->author) : '' ?>
                                <?= $note->visibleToCustomer ? ' · enviada al cliente' : '' ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </aside>
</div>
