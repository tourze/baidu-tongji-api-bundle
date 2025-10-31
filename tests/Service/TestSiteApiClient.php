<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Psr\Log\NullLogger;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Service\TongjiApiClient;

/**
 * 测试用的站点API客户端
 * 为测试目的继承TongjiApiClient以保持类型兼容性
 *
 * @internal
 */
/** @phpstan-ignore-next-line class.extendsNonAbstractClass */
final class TestSiteApiClient extends TongjiApiClient
{
    /** @var array<string, mixed> */
    private array $responseData;

    /** @param array<string, mixed> $responseData */
    public function __construct(array $responseData)
    {
        parent::__construct(
            new TestHttpClient(),
            new NullLogger()
        );
        $this->responseData = $responseData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSiteList(BaiduOAuth2User $user): array
    {
        return $this->responseData;
    }
}
