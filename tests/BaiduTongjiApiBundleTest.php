<?php

namespace Tourze\BaiduTongjiApiBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduTongjiApiBundle\BaiduTongjiApiBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(BaiduTongjiApiBundle::class)]
#[RunTestsInSeparateProcesses]
final class BaiduTongjiApiBundleTest extends AbstractBundleTestCase
{
}
