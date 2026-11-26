<?php

declare(strict_types=1);

namespace WooPanel\Http\Controller;

use WooPanel\Application\Error\StoreRejected;
use WooPanel\Application\ImageUpload;
use WooPanel\Application\InvalidImage;
use WooPanel\Application\Port\ProductGateway;
use WooPanel\Application\ProductQuery;
use WooPanel\Application\SaveProduct;
use WooPanel\Domain\InvalidProductDraft;
use WooPanel\Domain\Product;
use WooPanel\Domain\ProductDraft;
use WooPanel\Domain\ProductStatus;
use WooPanel\Domain\StockStatus;
use WooPanel\Http\Request;
use WooPanel\Http\Responder;
use WooPanel\Http\Response;

final readonly class ProductController
{
    public function __construct(
        private ProductGateway $products,
        private SaveProduct $save,
        private Responder $respond,
    ) {}

    public function index(Request $request): Response
    {
        $query = new ProductQuery($request->pageNumber(), $request->query('buscar'), StockStatus::tryFrom($request->query('existencias')));

        return $this->respond->page('products/index', [
            'page' => $this->products->list($query),
            'query' => $query,
            'stockStatuses' => StockStatus::cases(),
        ], 'Productos');
    }

    public function create(): Response
    {
        return $this->form(null, new ProductDraft(), [], 200);
    }

    public function edit(int $id): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return $this->notFound($id);
        }

        return $this->form($product, $product->toDraft(), [], 200);
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request, int $id): Response
    {
        $product = $this->products->find($id);
        if ($product === null) {
            return $this->notFound($id);
        }

        return $this->save($request, $product);
    }

    public function trash(int $id): Response
    {
        try {
            $this->products->trash($id);
        } catch (StoreRejected $e) {
            return $this->respond->redirect('/productos/' . $id, 'error', $e->getMessage());
        }

        return $this->respond->redirect('/productos', 'success', 'Producto enviado a la papelera de WordPress. Puedes restaurarlo desde allí.');
    }

    private function save(Request $request, ?Product $product): Response
    {
        try {
            $draft = ProductDraft::fromInput($request->body);
        } catch (InvalidProductDraft $e) {
            return $this->form($product, $e->draft, $e->errors, 422);
        }

        try {
            $saved = ($this->save)($product?->id, $draft, self::image($request));
        } catch (InvalidImage $e) {
            return $this->form($product, $draft, ['imagen' => $e->getMessage()], 422);
        } catch (StoreRejected $e) {
            return $this->form($product, $draft, ['_form' => $e->getMessage()], 422);
        }

        return $this->respond->redirect(
            '/productos/' . $saved->id,
            'success',
            $product === null ? \sprintf('Producto «%s» creado.', $saved->name) : 'Cambios guardados.',
        );
    }

    /**
     * @param array<string, string> $errors
     */
    private function form(?Product $product, ProductDraft $draft, array $errors, int $status): Response
    {
        return $this->respond->page('products/form', [
            'product' => $product,
            'draft' => $draft,
            'errors' => $errors,
            'statuses' => ProductStatus::cases(),
            'maxImageMb' => ImageUpload::MAX_BYTES / 1024 / 1024,
        ], $product === null ? 'Nuevo producto' : 'Editar ' . $product->name, $status);
    }

    private function notFound(int $id): Response
    {
        return $this->respond->error(404, 'Producto no encontrado', \sprintf('No existe un producto con el ID %d o ya está en la papelera.', $id));
    }

    /**
     * @throws InvalidImage
     */
    private static function image(Request $request): ?ImageUpload
    {
        $file = $request->file('imagen');
        if ($file === null) {
            return null;
        }

        return match ($file['error']) {
            \UPLOAD_ERR_OK => ImageUpload::fromFile($file['tmp_name'], $file['name']),
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => throw new InvalidImage('La imagen supera el máximo de 5 MB.'),
            default => throw new InvalidImage('No pudimos recibir la imagen. Vuelve a intentarlo.'),
        };
    }
}
