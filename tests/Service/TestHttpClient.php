<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 简单的测试HTTP客户端
 *
 * @internal
 */
final class TestHttpClient implements HttpClientInterface
{
    private ?ResponseInterface $expectedResponse = null;

    public function setExpectedResponse(ResponseInterface $response): void
    {
        $this->expectedResponse = $response;
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->expectedResponse ?? new TestResponse('', 200);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return new class implements ResponseStreamInterface {
            /**
             * @return \Traversable<int, ChunkInterface>
             */
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }

            public function current(): ChunkInterface
            {
                return new class implements ChunkInterface {
                    public function isTimeout(): bool
                    {
                        return false;
                    }

                    public function isFirst(): bool
                    {
                        return false;
                    }

                    public function isLast(): bool
                    {
                        return false;
                    }

                    /**
                     * @return array<string, mixed>|null
                     */
                    public function getInformationalStatus(): ?array
                    {
                        return null;
                    }

                    public function getContent(): string
                    {
                        return '';
                    }

                    public function getOffset(): int
                    {
                        return 0;
                    }

                    public function getError(): ?string
                    {
                        return null;
                    }
                };
            }

            public function key(): ResponseInterface
            {
                return new TestResponse('', 200);
            }

            public function next(): void
            {
            }

            public function rewind(): void
            {
            }

            public function valid(): bool
            {
                return false;
            }
        };
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function withOptions(array $options): static
    {
        return new self();
    }
}
