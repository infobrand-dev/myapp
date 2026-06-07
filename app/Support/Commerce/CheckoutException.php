<?php

namespace App\Support\Commerce;

use RuntimeException;

class CheckoutException extends RuntimeException
{
    /**
     * @param  array<string, string|array<int, string>>  $errors
     * @param  array<string, mixed>  $flash
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        private readonly array $flash = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function flash(): array
    {
        return $this->flash;
    }
}
