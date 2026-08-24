<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class OrderValidationException extends RuntimeException
{
    /**
     * @param array<string, mixed> $errors Validation errors.
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The order payload is invalid.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
