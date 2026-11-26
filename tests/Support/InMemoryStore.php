<?php

declare(strict_types=1);

namespace WooPanel\Tests\Support;

use WooPanel\Application\Error\StoreError;
use WooPanel\Application\Store;

/**
 * In-memory store whose gateways share one switch to make the next call fail.
 */
final class InMemoryStore
{
    public ?StoreError $failure = null;

    public readonly InMemoryOrders $orders;
    public readonly InMemoryProducts $products;
    public readonly InMemoryReports $reports;
    public readonly InMemoryMedia $media;

    public function __construct()
    {
        $this->orders = new InMemoryOrders($this);
        $this->products = new InMemoryProducts($this);
        $this->reports = new InMemoryReports($this);
        $this->media = new InMemoryMedia($this);
    }

    public function store(): Store
    {
        return new Store($this->orders, $this->products, $this->reports, $this->media);
    }

    public function failIfAsked(): void
    {
        if ($this->failure !== null) {
            $failure = $this->failure;
            $this->failure = null;

            throw $failure;
        }
    }
}
