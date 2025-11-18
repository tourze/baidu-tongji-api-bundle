<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Tests\Controller\Admin;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\TongjiSubDirectoryCrudController;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 百度统计子目录CRUD控制器测试
 *
 * @internal
 */
#[CoversClass(TongjiSubDirectoryCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TongjiSubDirectoryCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return TongjiSubDirectory::class;
    }

    protected function getControllerService(): TongjiSubDirectoryCrudController
    {
        return self::getService(TongjiSubDirectoryCrudController::class);
    }

    /**
     * 提供索引页面的表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '子目录ID' => ['子目录ID'];
        yield '子目录路径' => ['子目录路径'];
        yield '子目录状态' => ['子目录状态'];
        yield '所属站点' => ['所属站点'];
    }

    /**
     * 创建页面需要用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'subDirId' => ['subDirId'];
        yield 'subDir' => ['subDir'];
        yield 'site' => ['site'];
    }

    /**
     * 编辑页用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'subDirId' => ['subDirId'];
        yield 'subDir' => ['subDir'];
        yield 'status' => ['status'];
        yield 'site' => ['site'];
    }

    public function testConfigureCrud(): void
    {
        $controller = new TongjiSubDirectoryCrudController();
        // 使用真实的Crud实例，而不是Mock
        $crud = $controller->configureCrud(Crud::new());

        // 验证配置包含自定义的标签设置
        $crudDto = $crud->getAsDto();
        self::assertSame('百度统计子目录', $crudDto->getEntityLabelInSingular());
    }

    public function testConfigureActions(): void
    {
        $controller = new TongjiSubDirectoryCrudController();
        // 使用 Actions::new() 创建实际实例，避免 final 类 Mock 问题
        $actions = $controller->configureActions(Actions::new());

        // 验证是否包含基本的页面actions
        self::assertNotEmpty($actions->getAsDto('index')->getActions());
    }

    public function testConfigureFilters(): void
    {
        $controller = new TongjiSubDirectoryCrudController();
        // 使用 Filters::new() 创建实际实例，避免 final 类 Mock 问题
        $filters = $controller->configureFilters(Filters::new());

        // 验证过滤器配置返回 Filters 对象（已验证返回类型）
        self::assertIsObject($filters);
    }

    public function testConfigureFields(): void
    {
        $controller = new TongjiSubDirectoryCrudController();
        $fields = $controller->configureFields('index');

        self::assertIsIterable($fields);
        // 将 Generator 转换为数组以避免 Generator 断言问题
        $fieldsArray = iterator_to_array($fields);
        self::assertNotEmpty($fieldsArray);
    }

    public function testEntityPersistence(): void
    {
        $client = self::createAuthenticatedClient();

        /** @var EntityManagerInterface $entityManager */
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

        // 创建测试站点
        $site = new TongjiSite();
        $site->setSiteId('test_site_999');
        $site->setDomain('example.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));
        $site->setRawData(['site_id' => 'test_site_999', 'domain' => 'example.com']);

        $entityManager->persist($site);

        // 创建测试子目录
        $entity = new TongjiSubDirectory();
        $entity->setSubDirId('subdir_123');
        $entity->setSubDir('/blog/');
        $entity->setStatus(0);
        $entity->setSubDirCreateTime(new \DateTimeImmutable('2024-01-01'));
        $entity->setSite($site);
        $entity->setRawData(['sub_dir_id' => 'subdir_123', 'sub_dir' => '/blog/']);

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        $repository = $entityManager->getRepository(TongjiSubDirectory::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertSame('subdir_123', $savedEntity->getSubDirId());
        self::assertSame('/blog/', $savedEntity->getSubDir());
        self::assertSame(0, $savedEntity->getStatus());
        $entitySite = $savedEntity->getSite();
        self::assertNotNull($entitySite);
        self::assertSame($site->getId(), $entitySite->getId());
        self::assertSame('test_site_999', $entitySite->getSiteId());
    }

    public function testControllerInstantiation(): void
    {
        $controller = new TongjiSubDirectoryCrudController();
        // 验证控制器是否正确初始化 - 检查控制器的核心功能
        self::assertSame(TongjiSubDirectory::class, $controller::getEntityFqcn());
        self::assertTrue(class_exists(TongjiSubDirectoryCrudController::class));
    }

    public function testValidationErrors(): void
    {
        $entity = new TongjiSubDirectory();

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
        self::assertArrayHasKey('subDirId', $violationMessages);
        self::assertArrayHasKey('subDir', $violationMessages);
    }

    public function testSubDirectoryValidation(): void
    {
        $client = self::createAuthenticatedClient();

        /** @var EntityManagerInterface $entityManager */
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

        // 创建测试站点
        $site = new TongjiSite();
        $site->setSiteId('test_site_validation_' . uniqid());
        $site->setDomain('example.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        $entityManager->persist($site);

        // 测试空子目录ID验证
        $entity = new TongjiSubDirectory();
        $entity->setSubDirId('');
        $entity->setSubDir('/blog/');
        $entity->setStatus(0);
        $entity->setSite($site);

        // 手动触发验证
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        $this->assertGreaterThan(0, count($violations), '空子目录ID应该触发验证错误');

        // 检查具体的验证错误
        $firstViolation = $violations[0] ?? null;
        self::assertNotNull($firstViolation);
        $this->assertStringContainsString('subDirId', $firstViolation->getPropertyPath());
    }

    public function testValidSubDirectoryCreation(): void
    {
        $client = self::createAuthenticatedClient();

        /** @var EntityManagerInterface $entityManager */
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

        // 创建站点
        $site = new TongjiSite();
        $site->setSiteId('site_for_valid_subdir_' . uniqid());
        $site->setDomain('valid-site.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        $entityManager->persist($site);

        // 创建有效的子目录
        $entity = new TongjiSubDirectory();
        $entity->setSubDirId('valid_subdir_' . uniqid());
        $entity->setSubDir('/valid-path/');
        $entity->setStatus(0);
        $entity->setSubDirCreateTime(new \DateTimeImmutable('2024-01-01'));
        $entity->setSite($site);
        $entity->setRawData(['sub_dir_id' => 'valid', 'sub_dir' => '/valid-path/']);

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        $repository = $entityManager->getRepository(TongjiSubDirectory::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertStringStartsWith('valid_subdir_', $savedEntity->getSubDirId());
        self::assertSame('/valid-path/', $savedEntity->getSubDir());
        self::assertSame(0, $savedEntity->getStatus());
        $entitySite = $savedEntity->getSite();
        self::assertNotNull($entitySite);
        self::assertSame($site->getId(), $entitySite->getId());
    }

    public function testSubDirectoryPathValidation(): void
    {
        $client = self::createAuthenticatedClient();

        /** @var EntityManagerInterface $entityManager */
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

        // 创建站点
        $site = new TongjiSite();
        $site->setSiteId('site_for_path_validation_' . uniqid());
        $site->setDomain('path-validation.com');
        $site->setUser($user);
        $site->setStatus(0);
        $site->setSiteCreateTime(new \DateTimeImmutable('2024-01-01'));

        $entityManager->persist($site);

        // 测试空子目录路径
        $entity = new TongjiSubDirectory();
        $entity->setSubDirId('subdir_empty_path_' . uniqid());
        $entity->setSubDir('');
        $entity->setStatus(0);
        $entity->setSite($site);

        // 手动触发验证
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        $this->assertGreaterThan(0, count($violations), '空子目录路径应该触发验证错误');

        // 检查具体的验证错误
        $firstViolation = $violations[0] ?? null;
        self::assertNotNull($firstViolation);
        $this->assertStringContainsString('subDir', $firstViolation->getPropertyPath());
    }

    public function testSiteRelationshipValidation(): void
    {
        $client = self::createAuthenticatedClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        // 测试没有关联站点的子目录（会触发数据库约束异常）
        $entity = new TongjiSubDirectory();
        $entity->setSubDirId('orphan_subdir_' . uniqid());
        $entity->setSubDir('/orphan/');
        $entity->setStatus(0);
        // 故意不设置 site

        // 这种情况会触发数据库层面的NOT NULL约束异常
        $this->expectException(NotNullConstraintViolationException::class);

        $entityManager->persist($entity);
        $entityManager->flush();
    }
}
