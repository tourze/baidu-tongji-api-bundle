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
#[CoversClass(TongjiSite::class)]
final class TongjiSiteTest extends AbstractEntityTestCase
{
    private BaiduOAuth2User $user;

    private TongjiSite $site;

    protected function createEntity(): object
    {
        $config = new BaiduOAuth2Config();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_uid');
        $user->setAccessToken('token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        $site = new TongjiSite();
        $site->setSiteId('12345');
        $site->setDomain('example.com');
        $site->setUser($user);

        return $site;
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

        yield 'siteId' => ['siteId', 'new_site_123'];
        yield 'domain' => ['domain', 'new-domain.com'];
        yield 'status' => ['status', 1];
        yield 'siteCreateTime' => ['siteCreateTime', new \DateTimeImmutable()];
        yield 'rawData' => ['rawData', ['key' => 'value', 'status' => 'active']];
        yield 'user' => ['user', $user];
    }

    public function testConstruct(): void
    {
        $site = new TongjiSite();
        $site->setSiteId('123');
        $site->setDomain('test.com');
        $site->setUser($this->user);

        $this->assertSame('123', $site->getSiteId());
        $this->assertSame('test.com', $site->getDomain());
        $this->assertSame($this->user, $site->getUser());
        $this->assertSame(0, $site->getStatus());
        $this->assertCount(0, $site->getSubDirectories());
    }

    public function testToString(): void
    {
        $result = (string) $this->site;
        $this->assertSame('TongjiSite[12345] example.com', $result);
    }

    public function testSettersAndGetters(): void
    {
        $this->site->setSiteId('new_id');
        $this->assertSame('new_id', $this->site->getSiteId());

        $this->site->setDomain('new.com');
        $this->assertSame('new.com', $this->site->getDomain());

        $this->site->setStatus(1);
        $this->assertSame(1, $this->site->getStatus());

        $rawData = ['test' => 'data'];
        $this->site->setRawData($rawData);
        $this->assertSame($rawData, $this->site->getRawData());

        $createTime = new \DateTimeImmutable();
        $this->site->setSiteCreateTime($createTime);
        $this->assertSame($createTime, $this->site->getSiteCreateTime());
    }

    public function testSubDirectoryManagement(): void
    {
        $subDir1 = new TongjiSubDirectory();
        $subDir1->setSubDirId('sub1');
        $subDir1->setSubDir('/path1');
        $subDir2 = new TongjiSubDirectory();
        $subDir2->setSubDirId('sub2');
        $subDir2->setSubDir('/path2');

        $this->site->addSubDirectory($subDir1);
        $this->site->addSubDirectory($subDir2);

        $this->assertCount(2, $this->site->getSubDirectories());
        $this->assertTrue($this->site->getSubDirectories()->contains($subDir1));
        $this->assertTrue($this->site->getSubDirectories()->contains($subDir2));
        $this->assertSame($this->site, $subDir1->getSite());
        $this->assertSame($this->site, $subDir2->getSite());

        $this->site->addSubDirectory($subDir1);
        $this->assertCount(2, $this->site->getSubDirectories());

        $this->site->removeSubDirectory($subDir1);
        $this->assertCount(1, $this->site->getSubDirectories());
        $this->assertFalse($this->site->getSubDirectories()->contains($subDir1));
        $this->assertNull($subDir1->getSite());
    }

    public function testSetSiteCreateTimeWithDateTime(): void
    {
        $dateTime = new \DateTime('2023-01-01 12:00:00');
        $this->site->setSiteCreateTime($dateTime);

        $result = $this->site->getSiteCreateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2023-01-01', $result->format('Y-m-d'));
    }

    public function testSetSiteCreateTimeWithNull(): void
    {
        $this->site->setSiteCreateTime(null);
        $this->assertNull($this->site->getSiteCreateTime());
    }

    protected function setUp(): void
    {
        $config = new BaiduOAuth2Config();
        $this->user = new BaiduOAuth2User();
        $this->user->setBaiduUid('test_uid');
        $this->user->setAccessToken('token');
        $this->user->setExpiresIn(3600);
        $this->user->setConfig($config);
        $this->site = new TongjiSite();
        $this->site->setSiteId('12345');
        $this->site->setDomain('example.com');
        $this->site->setUser($this->user);
    }
}
