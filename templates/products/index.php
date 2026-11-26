<?php
/**
 * @var \WooPanel\View\View $this
 * @var \WooPanel\Domain\Page<\WooPanel\Domain\Product> $page
 * @var \WooPanel\Application\ProductQuery $query
 * @var list<\WooPanel\Domain\StockStatus> $stockStatuses
 */
$params = ['buscar' => $query->search, 'existencias' => $query->stockStatus->value ?? ''];
?>
<header class="page-head">
    <div>
        <h1>Productos</h1>
        <p class="muted"><?= $page->total ?> <?= $page->total === 1 ? 'producto' : 'productos' ?><?= $query->stockStatus !== null ? ' «' . $query->stockStatus->label() . '»' : '' ?></p>
    </div>
    <a class="button button--primary" href="/productos/nuevo"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>Nuevo producto</a>
</header>

<form class="filters" method="get" action="/productos" role="search">
    <label class="field field--grow">
        <span class="sr-only">Buscar productos</span>
        <input type="search" name="buscar" value="<?= $this->e($query->search) ?>" placeholder="Nombre o SKU" maxlength="100">
    </label>
    <label class="field">
        <span class="sr-only">Existencias</span>
        <select name="existencias">
            <option value="">Todas las existencias</option>
            <?php foreach ($stockStatuses as $stock): ?>
                <option value="<?= $stock->value ?>"<?= $query->stockStatus === $stock ? ' selected' : '' ?>><?= $stock->label() ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button" type="submit"><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>Filtrar</button>
    <?php if ($query->search !== '' || $query->stockStatus !== null): ?>
        <a class="link" href="/productos">Limpiar</a>
    <?php endif; ?>
</form>

<?php if ($page->items === []): ?>
    <p class="card empty">No encontramos productos con esos filtros.</p>
<?php else: ?>
    <div class="card table-wrap">
        <table class="table table--stack">
            <thead>
            <tr>
                <th scope="col"><span class="sr-only">Imagen</span></th>
                <th scope="col">Producto</th>
                <th scope="col">Publicación</th>
                <th scope="col" class="num">Precio</th>
                <th scope="col">Existencias</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($page->items as $product): ?>
                <tr>
                    <td class="thumb-cell">
                        <?php if ($product->imageUrl !== null): ?>
                            <img class="thumb" src="<?= $this->e($product->imageUrl) ?>" alt="" width="44" height="44" loading="lazy">
                        <?php else: ?>
                            <span class="thumb thumb--empty"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Producto">
                        <a class="link" href="/productos/<?= $product->id ?>"><strong><?= $this->e($product->name) ?></strong></a>
                        <?php if ($product->sku !== ''): ?><small class="muted block">SKU <?= $this->e($product->sku) ?></small><?php endif; ?>
                    </td>
                    <td data-label="Publicación"><?= $product->status->label() ?></td>
                    <td class="num" data-label="Precio">
                        <?php if ($product->isOnSale()): ?>
                            <del class="muted"><?= $this->price($product->regularPrice) ?></del>
                            <strong class="block"><?= $this->price($product->salePrice) ?></strong>
                        <?php else: ?>
                            <?= $this->price($product->regularPrice) ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Existencias">
                        <span class="badge badge--<?= $product->stockStatus->tone() ?>"><?= $product->stockStatus->label() ?></span>
                        <?php if ($product->manageStock && $product->stockQuantity !== null): ?><small class="muted block"><?= $product->stockQuantity ?> en bodega</small><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->partial('partials/pagination', ['page' => $page, 'path' => '/productos', 'params' => $params]) ?>
<?php endif; ?>
