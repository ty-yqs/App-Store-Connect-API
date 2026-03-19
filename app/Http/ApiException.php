<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly string $errorCode,
        string $message,
        private readonly mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): mixed
    {
        return $this->details;
    }
}
