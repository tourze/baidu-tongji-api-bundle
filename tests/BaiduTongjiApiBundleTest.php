<?php

namespace Tourze\BaiduTongjiApiBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\BaiduOauth2IntegrateBundle;
use Tourze\BaiduTongjiApiBundle\BaiduTongjiApiBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduTongjiApiBundle::class)]
#[RunTestsInSeparateProcesses]
final class BaiduTongjiApiBundleTest extends AbstractBundleTestCase
{
    public function testGetBundleDependencies(): void
    {
        $dependencies = BaiduTongjiApiBundle::getBundleDependencies();

        $expectedDependencies = [
            DoctrineBundle::class => ['all' => true],
            BaiduOauth2IntegrateBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
        ];

        $this->assertSame($expectedDependencies, $dependencies);
    }
}
