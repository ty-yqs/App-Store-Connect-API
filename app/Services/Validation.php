<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;

final class Validation
{
    public static function requireString(array $source, string $field, int $maxLength = 255): string
    {
        $value = $source[$field] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new ApiException(
                422,
                'validation_error',
                sprintf('Field "%s" is required.', $field),
                ['field' => $field]
            );
        }

        $value = trim($value);

        if (strlen($value) > $maxLength) {
            throw new ApiException(
                422,
                'validation_error',
                sprintf('Field "%s" exceeds max length %d.', $field, $maxLength),
                ['field' => $field, 'max_length' => $maxLength]
            );
        }

        return $value;
    }

    public static function optionalString(array $source, string $field, int $maxLength = 255): ?string
    {
        if (!array_key_exists($field, $source) || $source[$field] === null || $source[$field] === '') {
            return null;
        }

        if (!is_string($source[$field])) {
            throw new ApiException(
                422,
                'validation_error',
                sprintf('Field "%s" must be a string.', $field),
                ['field' => $field]
            );
        }

        return self::requireString($source, $field, $maxLength);
    }

    public static function normalizeListQuery(array $query): array
    {
        $allowed = ['limit', 'cursor', 'sort', 'include', 'fields', 'filter'];
        $normalized = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $query)) {
                continue;
            }

            $normalized[$key] = $query[$key];
        }

        if (array_key_exists('limit', $normalized)) {
            if (is_array($normalized['limit']) || !is_numeric((string) $normalized['limit'])) {
                throw new ApiException(422, 'validation_error', 'Query parameter "limit" must be an integer.');
            }

            $limit = (int) $normalized['limit'];
            if ($limit < 1 || $limit > 200) {
                throw new ApiException(422, 'validation_error', 'Query parameter "limit" must be between 1 and 200.');
            }

            $normalized['limit'] = $limit;
        }

        return $normalized;
    }

    public static function requireIdentifier(string $value, string $field = 'id'): string
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $value)) {
            throw new ApiException(
                422,
                'validation_error',
                sprintf('Field "%s" has invalid format.', $field),
                ['field' => $field]
            );
        }

        return $value;
    }
}
