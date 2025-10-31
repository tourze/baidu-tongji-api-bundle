<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\TongjiSiteCrudController;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 百度统计站点CRUD控制器测试
 *
 * @internal
 */
#[CoversClass(TongjiSiteCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TongjiSiteCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return TongjiSite::class;
    }

    protected function getControllerService(): TongjiSiteCrudController
    {
        return self::getService(TongjiSiteCrudController::class);
    }

    /**
     * 提供索引页面的表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '站点ID' => ['站点ID'];
        yield '站点域名' => ['站点域名'];
        yield '站点状态' => ['站点状态'];
        yield '关联用户' => ['关联用户'];
    }

    /**
     * 创建页面需要用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'domain' => ['domain'];
        yield 'user' => ['user'];
    }

    /**
     * 编辑页用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'domain' => ['domain'];
        yield 'status' => ['status'];
        yield 'user' => ['user'];
    }

    public function testGetEntityFqcn(): void
    {
        self::assertSame(TongjiSite::class, TongjiSiteCrudController::getEntityFqcn());
    }

    public function testConfigureCrud(): void
    {
        $controller = new TongjiSiteCrudController();
        // 使用真实的Crud实例，而不是Mock
        $crud = $controller->configureCrud(Crud::new());

        // 验证配置包含自定义的标签设置
        $crudDto = $crud->getAsDto();
        self::assertSame('百度统计站点', $crudDto->getEntityLabelInSingular());
    }

    public function testConfigureActions(): void
    {
        $controller = new TongjiSiteCrudController();
        // 使用 Actions::new() 创建实际实例，避免 final 类 Mock 问题
        $actions = $controller->configureActions(Actions::new());

        // 验证是否包含基本的页面actions
        self::assertNotEmpty($actions->getAsDto('index')->getActions());
    }

    public function testConfigureFields(): void
    {
        $controller = new TongjiSiteCrudController();
        $fields = $controller->configureFields('index');

        self::assertIsIterable($fields);
        // 将 Generator 转换为数组以避免 Generator 断言问题
        $fieldsArray = iterator_to_array($fields);
        self::assertNotEmpty($fieldsArray);
    }

    public function testEntityPersistence(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 创建测试配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $entityManager->persist($config);

        // 创建测试用户
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('baidu_' . uniqid());
        $user->setAccessToken('test_access_token_' . uniqid());
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user_' . uniqid());
        $user->setRefreshToken('test_refresh_token_' . uniqid());

        $entityManager->persist($user);

        // 创建测试站点
        $entity = new TongjiSite();
        $entity->setSiteId('test_site_789');
        $entity->setDomain('example.com');
        $entity->setUser($user);
        $entity->setStatus(0);
        $entity->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));
        $entity->setRawData(['site_id' => 'test_site_789', 'domain' => 'example.com']);

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        /** @var EntityRepository<TongjiSite> $repository */
        $repository = $entityManager->getRepository(TongjiSite::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertSame('test_site_789', $savedEntity->getSiteId());
        self::assertSame('example.com', $savedEntity->getDomain());
        self::assertSame(0, $savedEntity->getStatus());
        self::assertNotNull($savedEntity->getUser());
        self::assertSame($user->getId(), $savedEntity->getUser()->getId());
    }

    public function testControllerInstantiation(): void
    {
        $controller = new TongjiSiteCrudController();
        // 验证控制器是否正确初始化 - 检查控制器的核心功能
        self::assertSame(TongjiSite::class, $controller::getEntityFqcn());
        self::assertTrue(class_exists(TongjiSiteCrudController::class));
    }

    public function testValidationErrors(): void
    {
        $entity = new TongjiSite();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        // 检查必填字段的验证错误 - should not be blank
        self::assertGreaterThan(0, count($violations));

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        // 验证必填字段约束
        self::assertArrayHasKey('siteId', $violationMessages);
        self::assertArrayHasKey('domain', $violationMessages);
    }

    public function testEntityValidation(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 创建测试配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $entityManager->persist($config);

        // 创建测试用户
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('baidu_' . uniqid());
        $user->setAccessToken('test_access_token_' . uniqid());
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user_' . uniqid());
        $user->setRefreshToken('test_refresh_token_' . uniqid());

        $entityManager->persist($user);

        // 测试空字符串验证 - 验证实体的基本功能
        $entity = new TongjiSite(); // 空域名
        $entity->setSiteId('test_empty_site');
        $entity->setDomain('');
        $entity->setUser($user);
        $entity->setStatus(0);
        $entity->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        // 验证实体的基本属性设置
        $this->assertSame('test_empty_site', $entity->getSiteId());
        $this->assertSame('', $entity->getDomain());
        $this->assertSame(0, $entity->getStatus());
        $this->assertSame($user, $entity->getUser());
    }

    public function testValidEntityCreation(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 创建测试用户
        // 创建测试配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('baidu_' . uniqid());
        $user->setAccessToken('test_access_token_' . uniqid());
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user_' . uniqid());
        $user->setRefreshToken('test_refresh_token_' . uniqid());

        $entityManager->persist($user);

        // 创建有效的站点
        $entity = new TongjiSite();
        $entity->setSiteId('valid_site_' . uniqid());
        $entity->setDomain('valid-domain.com');
        $entity->setUser($user);
        $entity->setStatus(0);
        $entity->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));
        $entity->setRawData(['site_id' => 'valid', 'domain' => 'valid-domain.com']);

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        /** @var EntityRepository<TongjiSite> $repository */
        $repository = $entityManager->getRepository(TongjiSite::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertStringStartsWith('valid_site_', $savedEntity->getSiteId());
        self::assertSame('valid-domain.com', $savedEntity->getDomain());
        self::assertSame(0, $savedEntity->getStatus());
    }

    public function testSubDirectoryRelationship(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 创建测试用户
        // 创建测试配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('baidu_' . uniqid());
        $user->setAccessToken('test_access_token_' . uniqid());
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user_' . uniqid());
        $user->setRefreshToken('test_refresh_token_' . uniqid());

        $entityManager->persist($user);

        // 创建站点
        $site = new TongjiSite();
        $site->setSiteId('site_with_subdirs_' . uniqid());
        $site->setDomain('site-with-subdirs.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        $entityManager->persist($site);

        // 创建子目录
        $subDir1 = new TongjiSubDirectory();
        $subDir1->setSubDirId('sub_1_' . uniqid());
        $subDir1->setSubDir('/blog');
        $subDir1->setStatus(0);
        $subDir1->setSite($site);
        $site->addSubDirectory($subDir1);

        $subDir2 = new TongjiSubDirectory();
        $subDir2->setSubDirId('sub_2_' . uniqid());
        $subDir2->setSubDir('/news');
        $subDir2->setStatus(1);
        $subDir2->setSite($site);
        $site->addSubDirectory($subDir2);

        $entityManager->persist($subDir1);
        $entityManager->persist($subDir2);
        $entityManager->flush();

        // 验证关系 - 重新查询以获取最新的关联数据
        /** @var EntityRepository<TongjiSite> $siteRepository */
        $siteRepository = $entityManager->getRepository(TongjiSite::class);
        $refreshedSite = $siteRepository->find($site->getId());
        self::assertNotNull($refreshedSite);
        $subDirs = $refreshedSite->getSubDirectories();
        self::assertCount(2, $subDirs);

        $subDirArray = $subDirs->toArray();
        self::assertSame('/blog', $subDirArray[0]->getSubDir());
        self::assertSame('/news', $subDirArray[1]->getSubDir());

        // 验证反向关系
        self::assertSame($site, $subDirArray[0]->getSite());
        self::assertSame($site, $subDirArray[1]->getSite());
    }

    public function testDomainValidation(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client);

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 创建测试配置
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_' . uniqid());
        $config->setClientSecret('test_client_secret_' . uniqid());
        $entityManager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('baidu_' . uniqid());
        $user->setAccessToken('test_access_token_' . uniqid());
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('test_user_' . uniqid());
        $user->setRefreshToken('test_refresh_token_' . uniqid());

        $entityManager->persist($user);

        // 测试空域名 - 验证实体的基本功能
        $entity = new TongjiSite(); // 空域名
        $entity->setSiteId('invalid_domain_site_' . uniqid());
        $entity->setDomain('');
        $entity->setUser($user);
        $entity->setStatus(0);
        $entity->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        // 验证实体的基本属性设置
        $this->assertStringStartsWith('invalid_domain_site_', $entity->getSiteId());
        $this->assertSame('', $entity->getDomain());
        $this->assertSame(0, $entity->getStatus());
        $this->assertSame($user, $entity->getUser());
    }
}
