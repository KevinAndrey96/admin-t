<?php

declare(strict_types=1);

namespace WooPanel\Application\Error;

/**
 * Base for every failure that comes from talking to the store.
 */
abstract class StoreError extends \RuntimeException {}
