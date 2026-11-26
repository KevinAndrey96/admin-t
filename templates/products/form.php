<?php
/**
 * @var \WooPanel\View\View $this
 * @var string $csrf
 * @var \WooPanel\Domain\Product|null $product
 * @var \WooPanel\Domain\ProductDraft $draft
 * @var array<string, string> $errors
 * @var list<\WooPanel\Domain\ProductStatus> $statuses
 * @var int|float $maxImageMb
 */
use WooPanel\Domain\ProductDraft;
use WooPanel\Domain\ProductStatus;

$action = $product === null ? '/productos' : '/productos/' . $product->id;
$error = function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<p class="field__error" id="error-' . $field . '">' . $this->e($errors[$field]) . '</p>' : '';
};
$invalid = static fn(string $field): string => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="error-' . $field . '"' : '';
?>
<a class="back" href="/productos"><svg class="icon" aria-hidden="true"><use href="#i-back"/></svg>Productos</a>
<header class="page-head">
    <div>
        <h1><?= $product === null ? 'Nuevo producto' : $this->e($product->name) ?></h1>
        <p class="muted"><?= $product === null ? 'Producto simple con precio y existencias.' : 'ID ' . $product->id ?></p>
    </div>
    <?php if ($product !== null && $product->status === ProductStatus::Publish && $product->permalink !== ''): ?>
        <a class="button button--ghost" href="<?= $this->e($product->permalink) ?>" target="_blank" rel="noopener"><svg class="icon" aria-hidden="true"><use href="#i-store"/></svg>Ver en la tienda</a>
    <?php endif; ?>
</header>

<?php if (isset($errors['_form'])): ?>
    <p class="alert" role="alert">WooCommerce no aceptó los cambios: <?= $this->e($errors['_form']) ?></p>
<?php elseif ($errors !== []): ?>
    <p class="alert" role="alert">Revisa los campos marcados.</p>
<?php endif; ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="columns columns--wide" data-busy novalidate>
    <input type="hidden" name="_token" value="<?= $this->e($csrf) ?>">
    <div class="stack">
        <section class="card stack">
            <label class="field">
                <span>Nombre</span>
                <input name="name" value="<?= $this->e($draft->name) ?>" required maxlength="<?= ProductDraft::NAME_MAX ?>"<?= $invalid('name') ?>>
                <?= $error('name') ?>
            </label>
            <label class="field">
                <span>Descripción corta <small class="muted">opcional</small></span>
                <textarea name="short_description" rows="4" maxlength="<?= ProductDraft::DESCRIPTION_MAX ?>"<?= $invalid('short_description') ?>><?= $this->e($draft->shortDescription) ?></textarea>
                <?= $error('short_description') ?>
            </label>
            <div class="grid-2">
                <label class="field">
                    <span>Precio normal</span>
                    <input name="regular_price" value="<?= $this->e($draft->regularPrice) ?>" inputmode="decimal" required placeholder="125000"<?= $invalid('regular_price') ?>>
                    <?= $error('regular_price') ?>
                </label>
                <label class="field">
                    <span>Precio de oferta <small class="muted">opcional</small></span>
                    <input name="sale_price" value="<?= $this->e($draft->salePrice) ?>" inputmode="decimal" placeholder="99000"<?= $invalid('sale_price') ?>>
                    <?= $error('sale_price') ?>
                </label>
            </div>
        </section>

        <section class="card stack">
            <h2>Inventario</h2>
            <label class="field">
                <span>SKU <small class="muted">opcional</small></span>
                <input name="sku" value="<?= $this->e($draft->sku) ?>" maxlength="<?= ProductDraft::SKU_MAX ?>" autocomplete="off"<?= $invalid('sku') ?>>
                <?= $error('sku') ?>
            </label>
            <label class="check">
                <input type="checkbox" name="manage_stock" value="1" data-toggles="stock_quantity"<?= $draft->manageStock ? ' checked' : '' ?>>
                <span>Controlar existencias</span>
            </label>
            <label class="field">
                <span>Unidades en bodega</span>
                <input name="stock_quantity" id="stock_quantity" value="<?= $draft->stockQuantity ?? '' ?>" inputmode="numeric" pattern="\d*"<?= $invalid('stock_quantity') ?>>
                <?= $error('stock_quantity') ?>
            </label>
        </section>
    </div>

    <aside class="stack">
        <section class="card stack">
            <h2>Publicación</h2>
            <label class="field">
                <span>Estado</span>
                <select name="status"<?= $invalid('status') ?>>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status->value ?>"<?= $draft->status === $status ? ' selected' : '' ?>><?= $status->label() ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $error('status') ?>
            </label>
            <button class="button button--primary button--block" type="submit"><?= $product === null ? 'Crear producto' : 'Guardar cambios' ?></button>
        </section>

        <section class="card stack">
            <h2>Imagen</h2>
            <figure class="preview" data-preview>
                <?php if ($product?->imageUrl !== null): ?>
                    <img src="<?= $this->e($product->imageUrl) ?>" alt="Imagen actual del producto">
                <?php else: ?>
                    <span class="preview__empty"><svg class="icon icon--lg" aria-hidden="true"><use href="#i-image"/></svg>Sin imagen</span>
                <?php endif; ?>
            </figure>
            <label class="field">
                <span><?= $product?->imageUrl !== null ? 'Reemplazar imagen' : 'Subir imagen' ?></span>
                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" data-preview-input<?= $invalid('imagen') ?>>
                <?= $error('imagen') ?>
            </label>
            <p class="hint">JPG, PNG o WebP de máximo <?= $maxImageMb ?> MB. Se guarda en la biblioteca de medios de WordPress.</p>
        </section>
    </aside>
</form>

<?php if ($product !== null): ?>
    <form method="post" action="/productos/<?= $product->id ?>/eliminar" class="danger-zone" data-confirm="¿Enviar «<?= $this->e($product->name) ?>» a la papelera? Podrás restaurarlo desde WordPress.">
        <input type="hidden" name="_token" value="<?= $this->e($csrf) ?>">
        <p><strong>Enviar a la papelera</strong><span class="muted block">El producto deja de verse en la tienda; puedes restaurarlo desde WordPress.</span></p>
        <button class="button button--danger" type="submit">Enviar a la papelera</button>
    </form>
<?php endif; ?>
