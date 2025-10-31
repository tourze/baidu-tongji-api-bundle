<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\BaiduTongjiApiBundle\Service\TongjiSiteService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiSiteService::class)]
#[RunTestsInSeparateProcesses]
final class TongjiSiteServiceTest extends AbstractIntegrationTestCase
{
    private TongjiSiteService $service;

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(TongjiSiteService::class, $this->service);
    }

    public function testGetSiteBySiteId(): void
    {
        $result = $this->service->getSiteBySiteId('non-existent');
        $this->assertNull($result);
    }

    public function testSyncUserSites(): void
    {
        // 创建测试用户和配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_baidu_uid');
        $user->setAccessToken('valid_access_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user');
        $entityManager->persist($user);

        // 使用TestSiteApiClient替代匿名类
        $mockApiResponse = [
            'list' => [
                [
                    'site_id' => 'site_12345',
                    'domain' => 'example.com',
                    'status' => 0,
                    'create_time' => 1672531200, // 2023-01-01 00:00:00
                    'sub_dir_list' => [
                        [
                            'sub_dir_id' => 'sub_67890',
                            'sub_dir' => '/blog',
                            'status' => 0,
                            'create_time' => 1672531260, // 2023-01-01 00:01:00
                        ],
                        [
                            'sub_dir_id' => 'sub_11111',
                            'sub_dir' => '/news',
                            'status' => 1,
                            'create_time' => 1672531320, // 2023-01-01 00:02:00
                        ],
                    ],
                ],
                [
                    'site_id' => 'site_54321',
                    'domain' => 'test.org',
                    'status' => 1,
                    'create_time' => 1672531380, // 2023-01-01 00:03:00
                    'sub_dir_list' => [],
                ],
            ],
        ];

        $mockApiClient = new TestSiteApiClient($mockApiResponse);

        // 替换服务中的 API 客户端
        $reflection = new \ReflectionClass($this->service);
        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);
        $apiClientProperty->setValue($this->service, $mockApiClient);

        // 执行同步操作
        $sites = $this->service->syncUserSites($user);

        // 验证返回结果
        $this->assertCount(2, $sites, '应该返回2个站点');

        // 验证第一个站点
        $site1 = $sites[0];
        $this->assertInstanceOf(TongjiSite::class, $site1);
        $this->assertSame('site_12345', $site1->getSiteId());
        $this->assertSame('example.com', $site1->getDomain());
        $this->assertSame(0, $site1->getStatus());
        $this->assertSame($user, $site1->getUser());

        // 验证时间戳转换
        $expectedCreateTime = \DateTimeImmutable::createFromFormat('U', '1672531200');
        $this->assertEquals($expectedCreateTime, $site1->getSiteCreateTime());

        // 验证原始数据存储
        $rawData = $site1->getRawData();
        $this->assertIsArray($rawData);
        $this->assertSame('site_12345', $rawData['site_id']);

        // 验证子目录同步
        $subDirs = $site1->getSubDirectories();
        $this->assertCount(2, $subDirs, '第一个站点应该有2个子目录');

        $subDirArray = $subDirs->toArray();

        // 验证第一个子目录
        $subDir1 = $subDirArray[0];
        $this->assertInstanceOf(TongjiSubDirectory::class, $subDir1);
        $this->assertSame('sub_67890', $subDir1->getSubDirId());
        $this->assertSame('/blog', $subDir1->getSubDir());
        $this->assertSame(0, $subDir1->getStatus());
        $this->assertSame($site1, $subDir1->getSite());

        // 验证第二个子目录
        $subDir2 = $subDirArray[1];
        $this->assertInstanceOf(TongjiSubDirectory::class, $subDir2);
        $this->assertSame('sub_11111', $subDir2->getSubDirId());
        $this->assertSame('/news', $subDir2->getSubDir());
        $this->assertSame(1, $subDir2->getStatus());

        // 验证第二个站点
        $site2 = $sites[1];
        $this->assertInstanceOf(TongjiSite::class, $site2);
        $this->assertSame('site_54321', $site2->getSiteId());
        $this->assertSame('test.org', $site2->getDomain());
        $this->assertSame(1, $site2->getStatus());
        $this->assertSame($user, $site2->getUser());

        // 验证第二个站点没有子目录
        $this->assertCount(0, $site2->getSubDirectories());

        // 验证数据库持久化 - 刷新后重新查询
        $entityManager->flush();
        $entityManager->clear();

        // 从数据库重新获取数据验证持久化
        $persistedSites = $this->service->getUserSites($user);
        $this->assertCount(2, $persistedSites, '数据库中应该有2个持久化的站点');

        // 通过 site_id 验证持久化的站点
        $persistedSite1 = $this->service->getSiteBySiteId('site_12345');
        $this->assertNotNull($persistedSite1, '站点1应该被持久化到数据库');
        $this->assertSame('example.com', $persistedSite1->getDomain());
        $this->assertCount(2, $persistedSite1->getSubDirectories(), '站点1应该有2个子目录');

        $persistedSite2 = $this->service->getSiteBySiteId('site_54321');
        $this->assertNotNull($persistedSite2, '站点2应该被持久化到数据库');
        $this->assertSame('test.org', $persistedSite2->getDomain());
        $this->assertCount(0, $persistedSite2->getSubDirectories(), '站点2应该没有子目录');
    }

    public function testSyncUserSitesWithEmptyApiResponse(): void
    {
        // 创建测试用户
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('empty_uid');
        $user->setAccessToken('valid_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $entityManager->persist($user);

        // 使用TestSiteApiClient返回空列表
        $mockApiClient = new TestSiteApiClient([]);

        // 替换 API 客户端
        $reflection = new \ReflectionClass($this->service);
        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);
        $apiClientProperty->setValue($this->service, $mockApiClient);

        // 执行同步
        $sites = $this->service->syncUserSites($user);

        // 验证结果
        $this->assertIsArray($sites);
        $this->assertEmpty($sites, '空响应应该返回空数组');
    }

    public function testSyncUserSitesWithInvalidData(): void
    {
        // 创建测试用户
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('invalid_uid');
        $user->setAccessToken('valid_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $entityManager->persist($user);

        // 使用TestSiteApiClient返回包含无效数据的响应
        $mockApiResponse = [
            'list' => [
                [
                    'site_id' => 'valid_site',
                    'domain' => 'valid.com',
                    'status' => 0,
                ],
                [
                    // 缺少必需字段的无效数据
                    'invalid' => 'data',
                ],
                [
                    'site_id' => '', // 空的 site_id
                    'domain' => 'empty-id.com',
                ],
                [
                    'site_id' => 'another_valid',
                    'domain' => 'another.com',
                    'status' => 1,
                    'sub_dir_list' => [
                        [
                            // 无效的子目录数据
                            'invalid_sub' => 'dir',
                        ],
                        [
                            'sub_dir_id' => 'valid_sub',
                            'sub_dir' => '/valid',
                            'status' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $mockApiClient = new TestSiteApiClient($mockApiResponse);

        // 替换 API 客户端
        $reflection = new \ReflectionClass($this->service);
        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);
        $apiClientProperty->setValue($this->service, $mockApiClient);

        // 执行同步
        $sites = $this->service->syncUserSites($user);

        // 验证结果 - 处理3个站点（包括空site_id的站点，因为isValidSiteData只检查是否为字符串，不检查是否为空）
        $this->assertCount(3, $sites, '应该处理3个有效站点（包括空site_id）');

        // 验证有效站点
        $validSite = $sites[0];
        $this->assertSame('valid_site', $validSite->getSiteId());
        $this->assertSame('valid.com', $validSite->getDomain());

        // 验证空site_id站点（虽然被处理，但site_id为空）
        $emptySite = $sites[1];
        $this->assertSame('', $emptySite->getSiteId());
        $this->assertSame('empty-id.com', $emptySite->getDomain());

        $anotherValidSite = $sites[2];
        $this->assertSame('another_valid', $anotherValidSite->getSiteId());
        $this->assertSame('another.com', $anotherValidSite->getDomain());

        // 验证只有有效的子目录被处理
        $this->assertCount(1, $anotherValidSite->getSubDirectories(), '应该只有1个有效子目录');
        $validSubDir = $anotherValidSite->getSubDirectories()->first();
        $this->assertNotFalse($validSubDir, '应该能获取到子目录');
        $this->assertSame('valid_sub', $validSubDir->getSubDirId());
        $this->assertSame('/valid', $validSubDir->getSubDir());
    }

    public function testSyncUserSitesUpdateExistingData(): void
    {
        // 创建测试用户
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');
        $container = self::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('update_uid');
        $user->setAccessToken('valid_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $entityManager->persist($user);

        // 先创建一个已存在的站点
        $existingSite = new TongjiSite();
        $existingSite->setSiteId('existing_site');
        $existingSite->setDomain('old-domain.com');
        $existingSite->setUser($user);
        $existingSite->setStatus(1);
        $entityManager->persist($existingSite);

        // 创建已存在的子目录
        $existingSubDir = new TongjiSubDirectory();
        $existingSubDir->setSubDirId('existing_sub');
        $existingSubDir->setSubDir('/old-path');
        $existingSubDir->setStatus(1);
        $existingSite->addSubDirectory($existingSubDir);
        $entityManager->persist($existingSubDir);

        $entityManager->flush();
        $entityManager->clear();

        // 使用TestSiteApiClient返回更新的数据
        $mockApiResponse = [
            'list' => [
                [
                    'site_id' => 'existing_site', // 同一个站点ID
                    'domain' => 'updated-domain.com', // 更新域名
                    'status' => 0, // 更新状态
                    'create_time' => 1672617600, // 新时间戳
                    'sub_dir_list' => [
                        [
                            'sub_dir_id' => 'existing_sub',
                            'sub_dir' => '/updated-path', // 更新路径
                            'status' => 0, // 更新状态
                        ],
                        [
                            'sub_dir_id' => 'new_sub',
                            'sub_dir' => '/new-path',
                            'status' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $mockApiClient = new TestSiteApiClient($mockApiResponse);

        // 替换 API 客户端
        $reflection = new \ReflectionClass($this->service);
        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);
        $apiClientProperty->setValue($this->service, $mockApiClient);

        // 执行同步
        $sites = $this->service->syncUserSites($user);

        // 验证更新结果
        $this->assertCount(1, $sites);
        $updatedSite = $sites[0];

        // 验证站点数据被更新
        $this->assertSame('existing_site', $updatedSite->getSiteId());
        $this->assertSame('updated-domain.com', $updatedSite->getDomain(), '域名应该被更新');
        $this->assertSame(0, $updatedSite->getStatus(), '状态应该被更新');

        // 验证时间戳被更新
        $expectedTime = \DateTimeImmutable::createFromFormat('U', '1672617600');
        $this->assertEquals($expectedTime, $updatedSite->getSiteCreateTime(), '时间戳应该被更新');

        // 验证子目录更新和新增
        $subDirs = $updatedSite->getSubDirectories();
        $this->assertCount(2, $subDirs, '应该有2个子目录（1个更新，1个新增）');

        // 从数据库验证持久化
        $entityManager->flush();
        $entityManager->clear();

        $persistedSite = $this->service->getSiteBySiteId('existing_site');
        $this->assertNotNull($persistedSite);
        $this->assertSame('updated-domain.com', $persistedSite->getDomain(), '数据库中的域名应该被更新');
        $this->assertCount(2, $persistedSite->getSubDirectories(), '数据库中应该有2个子目录');
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();
        /** @var TongjiSiteService $service */
        $service = $container->get(TongjiSiteService::class);
        $this->service = $service;
    }
}
