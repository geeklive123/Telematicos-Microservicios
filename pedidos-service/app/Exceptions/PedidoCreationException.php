<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PedidoCreationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status,
        ?Throwable $previous = null,
        private readonly bool $uncertain = false,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function isUncertain(): bool
    {
        return $this->uncertain;
    }

    public function report(): bool
    {
        return false;
    }
}
