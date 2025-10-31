<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiSubDirectory::class)]
final class TongjiSubDirectoryTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');

        return $subDir;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $config = new BaiduOAuth2Config();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid');
        $user->setAccessToken('token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $site = new TongjiSite();
        $site->setSiteId('site123');
        $site->setDomain('test.com');
        $site->setUser($user);

        yield 'subDirId' => ['subDirId', 'new_subdir_456'];
        yield 'subDir' => ['subDir', '/news/category'];
        yield 'status' => ['status', 1];
        yield 'subDirCreateTime' => ['subDirCreateTime', new \DateTimeImmutable()];
        yield 'rawData' => ['rawData', ['path' => '/news', 'active' => true]];
        yield 'site' => ['site', $site];
    }

    public function testConstruct(): void
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');

        $this->assertSame('sub123', $subDir->getSubDirId());
        $this->assertSame('/blog', $subDir->getSubDir());
        $this->assertSame(0, $subDir->getStatus());
        $this->assertNull($subDir->getSite());
    }

    public function testToString(): void
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');
        $result = (string) $subDir;

        $this->assertSame('TongjiSubDir[sub123] /blog', $result);
    }

    public function testSettersAndGetters(): void
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');

        $subDir->setSubDirId('new_id');
        $this->assertSame('new_id', $subDir->getSubDirId());

        $subDir->setSubDir('/news');
        $this->assertSame('/news', $subDir->getSubDir());

        $subDir->setStatus(1);
        $this->assertSame(1, $subDir->getStatus());

        $rawData = ['test' => 'data'];
        $subDir->setRawData($rawData);
        $this->assertSame($rawData, $subDir->getRawData());

        $createTime = new \DateTimeImmutable();
        $subDir->setSubDirCreateTime($createTime);
        $this->assertSame($createTime, $subDir->getSubDirCreateTime());
    }

    public function testSiteRelation(): void
    {
        $config = new BaiduOAuth2Config();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid');
        $user->setAccessToken('token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);
        $site = new TongjiSite();
        $site->setSiteId('123');
        $site->setDomain('test.com');
        $site->setUser($user);
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');

        $subDir->setSite($site);
        $this->assertSame($site, $subDir->getSite());

        $subDir->setSite(null);
        $this->assertNull($subDir->getSite());
    }

    public function testSetSubDirCreateTimeWithDateTime(): void
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');
        $dateTime = new \DateTime('2023-01-01 12:00:00');

        $subDir->setSubDirCreateTime($dateTime);

        $result = $subDir->getSubDirCreateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2023-01-01', $result->format('Y-m-d'));
    }

    public function testSetSubDirCreateTimeWithNull(): void
    {
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId('sub123');
        $subDir->setSubDir('/blog');
        $subDir->setSubDirCreateTime(null);

        $this->assertNull($subDir->getSubDirCreateTime());
    }
}
