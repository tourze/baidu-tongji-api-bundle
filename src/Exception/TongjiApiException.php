<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Exception;

class TongjiApiException extends \Exception
{
    public static function accessTokenExpired(): self
    {
        return new self('Access token expired');
    }

    public static function httpError(string $message): self
    {
        return new self('HTTP request failed: ' . $message);
    }

    public static function invalidResponse(string $message): self
    {
        return new self('Invalid API response: ' . $message);
    }

    public static function apiError(string $message): self
    {
        return new self('Baidu Tongji API error: ' . $message);
    }

    public static function invalidMethodCall(string $method): self
    {
        return new self('Unknown method: ' . $method);
    }
}
