<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $filePath): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        if (!is_file($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }

            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower(self::get($key, $default ? 'true' : 'false'));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default): int
    {
        $value = self::get($key, (string) $default);

        if (!is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
