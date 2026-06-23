<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productName = 'an item')
    {
        parent::__construct("Sorry, {$productName} just went out of stock.");
    }
}
