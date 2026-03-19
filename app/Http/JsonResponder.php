<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\Env;
use Throwable;

final class JsonResponder
{
    public static function success(int $statusCode, mixed $data, string $requestId): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'success' => true,
                'request_id' => $requestId,
                'data' => $data,
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public static function error(Throwable $throwable, string $requestId): void
    {
        $statusCode = 500;
        $errorCode = 'internal_error';
        $message = 'An unexpected error occurred.';
        $details = null;

        if ($throwable instanceof ApiException) {
            $statusCode = $throwable->statusCode();
            $errorCode = $throwable->errorCode();
            $message = $throwable->getMessage();
            $details = $throwable->details();
        } elseif (Env::bool('APP_DEBUG', false)) {
            $details = [
                'type' => get_class($throwable),
                'message' => $throwable->getMessage(),
            ];
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'success' => false,
                'request_id' => $requestId,
                'error' => [
                    'code' => $errorCode,
                    'message' => $message,
                    'details' => $details,
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
