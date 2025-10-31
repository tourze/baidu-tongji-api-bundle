<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSubDirectoryRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiSubDirectoryRepository::class)]
#[RunTestsInSeparateProcesses]
final class TongjiSubDirectoryRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): TongjiSubDirectoryRepository
    {
        return self::getService(TongjiSubDirectoryRepository::class);
    }

    protected function createNewEntity(): object
    {
        $site = $this->createTongjiSite(uniqid('site_', true), uniqid() . '.example.com', uniqid('user_', true));
        $uniqueId = uniqid('subdir_', true);
        $subDir = new TongjiSubDirectory();
        $subDir->setSubDirId($uniqueId);
        $subDir->setSubDir('/' . uniqid('path_', true) . '/');
        $subDir->setSite($site);
        $subDir->setStatus(0);

        return $subDir;
    }

    protected function onSetUp(): void
    {
        // No additional setup needed
    }

    public function testSaveAndFindTongjiSubDirectory(): void
    {
        $site = $this->createTongjiSite('testsite1', 'example1.com', 'user_save_find');
        $subDirectory = new TongjiSubDirectory();
        $subDirectory->setSubDirId('subdir123');
        $subDirectory->setSubDir('/blog/');
        $subDirectory->setSite($site);
        $subDirectory->setStatus(0);
        $subDirectory->setSubDirCreateTime(new \DateTimeImmutable('2024-01-01'));
        $subDirectory->setRawData(['sub_dir' => '/blog/', 'status' => 0]);

        $this->getRepository()->save($subDirectory);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find($subDirectory->getId());
        $this->assertNotNull($found);
        $this->assertSame('subdir123', $found->getSubDirId());
        $this->assertSame('/blog/', $found->getSubDir());
        $this->assertSame(0, $found->getStatus());
        $this->assertNotNull($found->getSite());
        $this->assertSame($site->getId(), $found->getSite()->getId());
    }

    public function testFindBySubDirId(): void
    {
        $site = $this->createTongjiSite('testsite2', 'example2.com', 'user_find_subdir');
        $subDir1 = new TongjiSubDirectory();
        $subDir1->setSubDirId('subdir123');
        $subDir1->setSubDir('/blog/');
        $subDir1->setSite($site);
        $subDir2 = new TongjiSubDirectory();
        $subDir2->setSubDirId('subdir456');
        $subDir2->setSubDir('/news/');
        $subDir2->setSite($site);

        $this->getRepository()->save($subDir1);
        $this->getRepository()->save($subDir2);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->findBySubDirId('subdir123');
        $this->assertNotNull($found);
        $this->assertSame('subdir123', $found->getSubDirId());
        $this->assertSame('/blog/', $found->getSubDir());

        $notFound = $this->getRepository()->findBySubDirId('nonexistent');
        $this->assertNull($notFound);
    }

    public function testFindBySite(): void
    {
        $site1 = $this->createTongjiSite('site1', 'example.com', 'user1');
        $site2 = $this->createTongjiSite('site2', 'test.com', 'user2');

        $subDir1 = new TongjiSubDirectory();
        $subDir1->setSubDirId('subdir123');
        $subDir1->setSubDir('/news/');
        $subDir1->setSite($site1);
        $subDir2 = new TongjiSubDirectory();
        $subDir2->setSubDirId('subdir456');
        $subDir2->setSubDir('/blog/');
        $subDir2->setSite($site1);
        $subDir3 = new TongjiSubDirectory();
        $subDir3->setSubDirId('subdir789');
        $subDir3->setSubDir('/admin/');
        $subDir3->setSite($site2);

        $this->getRepository()->save($subDir1);
        $this->getRepository()->save($subDir2);
        $this->getRepository()->save($subDir3);
        self::getEntityManager()->flush();

        $site1SubDirs = $this->getRepository()->findBySite($site1);
        $this->assertCount(2, $site1SubDirs);

        $paths = array_map(fn ($sd) => $sd->getSubDir(), $site1SubDirs);
        $this->assertContains('/news/', $paths);
        $this->assertContains('/blog/', $paths);
        $this->assertNotContains('/admin/', $paths);

        // 验证按subDir排序
        $this->assertSame('/blog/', $site1SubDirs[0]->getSubDir());
        $this->assertSame('/news/', $site1SubDirs[1]->getSubDir());
    }

    public function testFindActiveBysite(): void
    {
        $site = $this->createTongjiSite('testsite3', 'example3.com', 'user_find_active');
        $activeSubDir = new TongjiSubDirectory();
        $activeSubDir->setSubDirId('subdir123');
        $activeSubDir->setSubDir('/active/');
        $activeSubDir->setSite($site);
        $activeSubDir->setStatus(0); // 正常状态

        $pausedSubDir = new TongjiSubDirectory();
        $pausedSubDir->setSubDirId('subdir456');
        $pausedSubDir->setSubDir('/paused/');
        $pausedSubDir->setSite($site);
        $pausedSubDir->setStatus(1); // 暂停状态

        $this->getRepository()->save($activeSubDir);
        $this->getRepository()->save($pausedSubDir);
        self::getEntityManager()->flush();

        $activeSubDirs = $this->getRepository()->findActiveBysite($site);
        $this->assertCount(1, $activeSubDirs);
        $this->assertSame('/active/', $activeSubDirs[0]->getSubDir());
        $this->assertSame(0, $activeSubDirs[0]->getStatus());
    }

    public function testRemove(): void
    {
        $site = $this->createTongjiSite('testsite5', 'example5.com', 'user_remove');
        $subDirectory = new TongjiSubDirectory();
        $subDirectory->setSubDirId('subdir123');
        $subDirectory->setSubDir('/test/');
        $subDirectory->setSite($site);

        $this->getRepository()->save($subDirectory, true);
        $subDirId = $subDirectory->getId();

        $this->getRepository()->remove($subDirectory);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find($subDirId);
        $this->assertNull($found);
    }

    private function createTongjiSite(string $siteId = 'testsite123', string $domain = 'example.com', string $userId = 'testuser123'): TongjiSite
    {
        $user = $this->createBaiduUser($userId);
        $site = new TongjiSite();
        $site->setSiteId($siteId);
        $site->setDomain($domain);
        $site->setUser($user);

        self::getEntityManager()->persist($site);
        self::getEntityManager()->flush();

        return $site;
    }

    private function createBaiduUser(string $uid = 'testuser123'): BaiduOAuth2User
    {
        $config = $this->createBaiduConfig($uid . '_config');
        $user = new BaiduOAuth2User();
        $user->setBaiduUid($uid);
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        return $user;
    }

    private function createBaiduConfig(string $clientId = 'test_client_id'): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId($clientId);
        $config->setClientSecret('test_client_secret');
        $config->setScope('basic');

        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        return $config;
    }
}
