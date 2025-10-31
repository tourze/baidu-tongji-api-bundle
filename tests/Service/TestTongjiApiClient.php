<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Psr\Log\NullLogger;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Service\TongjiApiClient;

/**
 * 测试用的TongjiApiClient实现
 * 为测试目的继承TongjiApiClient以保持类型兼容性
 *
 * @internal
 */
/** @phpstan-ignore-next-line class.extendsNonAbstractClass */
final class TestTongjiApiClient extends TongjiApiClient
{
    /** @var array<string, mixed> */
    private array $responseData;

    private int $callCount = 0;

    private int $expectedCalls = 1;

    /** @param array<string, mixed> $responseData */
    public function __construct(array $responseData, int $expectedCalls = 1)
    {
        parent::__construct(
            new TestHttpClient(),
            new NullLogger()
        );
        $this->responseData = $responseData;
        $this->expectedCalls = $expectedCalls;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getTrendTimeReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        ++$this->callCount;
        // 验证调用次数
        if ($this->callCount > $this->expectedCalls) {
            throw new \RuntimeException('Unexpected additional call to getTrendTimeReport');
        }

        return $this->responseData;
    }
}
