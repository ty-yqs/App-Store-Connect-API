<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;
use RuntimeException;

final class JwtSigner
{
    public static function sign(array $payload, array $header, string $privateKey): string
    {
        $encodedHeader = self::base64UrlEncode(self::toJson($header));
        $encodedPayload = self::base64UrlEncode(self::toJson($payload));
        $signingInput = $encodedHeader . '.' . $encodedPayload;

        $signature = self::signInput($signingInput, $privateKey);

        return $signingInput . '.' . self::base64UrlEncode($signature);
    }

    private static function signInput(string $message, string $privateKey): string
    {
        $opensslKey = openssl_pkey_get_private($privateKey);
        if ($opensslKey === false) {
            throw new ApiException(500, 'private_key_invalid', 'Private key format is invalid.');
        }

        $signature = '';
        $ok = openssl_sign($message, $signature, $opensslKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new ApiException(500, 'token_sign_error', 'OpenSSL failed to sign JWT payload.');
        }

        return self::fromDer($signature, 64);
    }

    private static function toJson(array $input): string
    {
        $json = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new ApiException(500, 'json_encode_error', 'Failed to encode JWT data to JSON.');
        }

        return $json;
    }

    private static function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private static function fromDer(string $der, int $partLength): string
    {
        $hex = unpack('H*', $der)[1] ?? '';
        if (substr($hex, 0, 2) !== '30') {
            throw new RuntimeException('Invalid DER sequence header.');
        }

        if (substr($hex, 2, 2) === '81') {
            $hex = substr($hex, 6);
        } else {
            $hex = substr($hex, 4);
        }

        if (substr($hex, 0, 2) !== '02') {
            throw new RuntimeException('Invalid DER integer marker for R.');
        }

        $rLength = hexdec(substr($hex, 2, 2));
        $rValue = self::retrievePositiveInteger(substr($hex, 4, $rLength * 2));
        $rValue = str_pad($rValue, $partLength, '0', STR_PAD_LEFT);

        $hex = substr($hex, 4 + ($rLength * 2));
        if (substr($hex, 0, 2) !== '02') {
            throw new RuntimeException('Invalid DER integer marker for S.');
        }

        $sLength = hexdec(substr($hex, 2, 2));
        $sValue = self::retrievePositiveInteger(substr($hex, 4, $sLength * 2));
        $sValue = str_pad($sValue, $partLength, '0', STR_PAD_LEFT);

        return pack('H*', $rValue . $sValue);
    }

    private static function retrievePositiveInteger(string $data): string
    {
        while (substr($data, 0, 2) === '00' && substr($data, 2, 2) > '7f') {
            $data = substr($data, 2);
        }

        return $data;
    }
}