<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 简单的测试响应类
 *
 * @internal
 */
final class TestResponse implements ResponseInterface
{
    public function __construct(
        private readonly string $content,
        private readonly int $statusCode,
    ) {
    }

    public function getContent(bool $throw = true): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $throw = true): array
    {
        return [];
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        return null;
    }
}
