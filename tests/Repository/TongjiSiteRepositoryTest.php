<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiSiteRepository::class)]
#[RunTestsInSeparateProcesses]
final class TongjiSiteRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): TongjiSiteRepository
    {
        return self::getService(TongjiSiteRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = $this->createBaiduUser(uniqid('user_', true));
        $uniqueSiteId = uniqid('site_', true);
        $site = new TongjiSite();
        $site->setSiteId($uniqueSiteId);
        $site->setDomain(uniqid() . '.example.com');
        $site->setUser($user);
        $site->setStatus(0);

        return $site;
    }

    protected function onSetUp(): void
    {
        // No additional setup needed
    }

    public function testSaveAndFindTongjiSite(): void
    {
        $user = $this->createBaiduUser();
        $site = new TongjiSite();
        $site->setSiteId('site123');
        $site->setDomain('example.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));
        $site->setRawData(['domain' => 'example.com', 'status' => 0]);

        $this->getRepository()->save($site);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find($site->getId());
        $this->assertNotNull($found);
        $this->assertSame('site123', $found->getSiteId());
        $this->assertSame('example.com', $found->getDomain());
        $this->assertSame(0, $found->getStatus());
        $this->assertNotNull($found->getUser());
        $this->assertSame($user->getId(), $found->getUser()->getId());
    }

    public function testFindBySiteId(): void
    {
        $user = $this->createBaiduUser();
        $site1 = new TongjiSite();
        $site1->setSiteId('site123');
        $site1->setDomain('example.com');
        $site1->setUser($user);
        $site2 = new TongjiSite();
        $site2->setSiteId('site456');
        $site2->setDomain('test.com');
        $site2->setUser($user);

        $this->getRepository()->save($site1);
        $this->getRepository()->save($site2);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->findBySiteId('site123');
        $this->assertNotNull($found);
        $this->assertSame('site123', $found->getSiteId());
        $this->assertSame('example.com', $found->getDomain());

        $notFound = $this->getRepository()->findBySiteId('nonexistent');
        $this->assertNull($notFound);
    }

    public function testFindByUser(): void
    {
        $user1 = $this->createBaiduUser('user1');
        $user2 = $this->createBaiduUser('user2');

        $site1 = new TongjiSite();
        $site1->setSiteId('site123');
        $site1->setDomain('example.com');
        $site1->setUser($user1);
        $site2 = new TongjiSite();
        $site2->setSiteId('site456');
        $site2->setDomain('test.com');
        $site2->setUser($user1);
        $site3 = new TongjiSite();
        $site3->setSiteId('site789');
        $site3->setDomain('other.com');
        $site3->setUser($user2);

        $this->getRepository()->save($site1);
        $this->getRepository()->save($site2);
        $this->getRepository()->save($site3);
        self::getEntityManager()->flush();

        $user1Sites = $this->getRepository()->findByUser($user1);
        $this->assertCount(2, $user1Sites);

        $domains = array_map(fn ($s) => $s->getDomain(), $user1Sites);
        $this->assertContains('example.com', $domains);
        $this->assertContains('test.com', $domains);
        $this->assertNotContains('other.com', $domains);

        // 验证按domain排序
        $this->assertSame('example.com', $user1Sites[0]->getDomain());
        $this->assertSame('test.com', $user1Sites[1]->getDomain());
    }

    public function testFindActiveSitesByUser(): void
    {
        $user = $this->createBaiduUser();
        $activeSite = new TongjiSite();
        $activeSite->setSiteId('site123');
        $activeSite->setDomain('active.com');
        $activeSite->setUser($user);
        $activeSite->setStatus(0); // 正常状态

        $pausedSite = new TongjiSite();
        $pausedSite->setSiteId('site456');
        $pausedSite->setDomain('paused.com');
        $pausedSite->setUser($user);
        $pausedSite->setStatus(1); // 暂停状态

        $this->getRepository()->save($activeSite);
        $this->getRepository()->save($pausedSite);
        self::getEntityManager()->flush();

        $activeSites = $this->getRepository()->findActiveSitesByUser($user);
        $this->assertCount(1, $activeSites);
        $this->assertSame('active.com', $activeSites[0]->getDomain());
        $this->assertSame(0, $activeSites[0]->getStatus());
    }

    public function testRemove(): void
    {
        $user = $this->createBaiduUser();
        $site = new TongjiSite();
        $site->setSiteId('site123');
        $site->setDomain('example.com');
        $site->setUser($user);

        $this->getRepository()->save($site, true);
        $siteId = $site->getId();

        $this->getRepository()->remove($site);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find($siteId);
        $this->assertNull($found);
    }

    private function createBaiduUser(string $uid = 'testuser123'): BaiduOAuth2User
    {
        $config = $this->createBaiduConfig();
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($uid);
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        return $user;
    }

    private function createBaiduConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');
        $config->setScope('basic');

        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        return $config;
    }
}
