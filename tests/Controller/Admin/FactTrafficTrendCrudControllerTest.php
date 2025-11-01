<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\FactTrafficTrendCrudController;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 流量趋势分析CRUD控制器测试
 *
 * @internal
 */
#[CoversClass(FactTrafficTrendCrudController::class)]
#[RunTestsInSeparateProcesses]
final class FactTrafficTrendCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return FactTrafficTrend::class;
    }

    protected function getControllerService(): FactTrafficTrendCrudController
    {
        return self::getService(FactTrafficTrendCrudController::class);
    }

    /**
     * 提供索引页面的表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '站点ID' => ['站点ID'];
        yield '统计日期' => ['统计日期'];
        yield '时间粒度' => ['时间粒度'];
        yield 'PV' => ['页面浏览量(PV)'];
        yield '访问次数' => ['访问次数'];
    }

    /**
     * 创建页面需要用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'date' => ['date'];
        yield 'gran' => ['gran'];
        yield 'pvCount' => ['pvCount'];
        yield 'visitCount' => ['visitCount'];
    }

    /**
     * 编辑页用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'date' => ['date'];
        yield 'gran' => ['gran'];
        yield 'pvCount' => ['pvCount'];
        yield 'visitCount' => ['visitCount'];
    }

    public function testGetEntityFqcn(): void
    {
        self::assertSame(FactTrafficTrend::class, FactTrafficTrendCrudController::getEntityFqcn());
    }

    public function testConfigureCrud(): void
    {
        $controller = new FactTrafficTrendCrudController();
        // 使用真实的Crud实例，而不是Mock
        $crud = $controller->configureCrud(Crud::new());

        // 验证配置包含自定义的标签设置
        $crudDto = $crud->getAsDto();
        self::assertSame('流量趋势分析', $crudDto->getEntityLabelInSingular());
    }

    public function testConfigureActions(): void
    {
        $controller = new FactTrafficTrendCrudController();
        // 使用 Actions::new() 创建实际实例，避免 final 类 Mock 问题
        $actions = $controller->configureActions(Actions::new());

        // 验证是否包含基本的页面actions
        self::assertNotEmpty($actions->getAsDto('index')->getActions());
    }

    public function testConfigureFilters(): void
    {
        $controller = new FactTrafficTrendCrudController();
        // 使用 Filters::new() 创建实际实例，避免 final 类 Mock 问题
        $filters = $controller->configureFilters(Filters::new());

        // 验证过滤器配置返回 Filters 对象（已验证返回类型）
        self::assertIsObject($filters);
    }

    public function testConfigureFields(): void
    {
        $controller = new FactTrafficTrendCrudController();
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

        // 创建测试实体
        $entity = new FactTrafficTrend();
        $entity->setSiteId('test_site_123');
        $entity->setDate(new \DateTimeImmutable('2024-01-01'));
        $entity->setGran('day');
        $entity->setPvCount(100);
        $entity->setVisitCount(50);
        $entity->setVisitorCount(30);
        $entity->setSourceType('search');
        $entity->setDevice('pc');
        $entity->setAreaScope('local');

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        $repository = $entityManager->getRepository(FactTrafficTrend::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertSame('test_site_123', $savedEntity->getSiteId());
        self::assertSame(100, $savedEntity->getPvCount());
        self::assertSame(50, $savedEntity->getVisitCount());
        self::assertSame(30, $savedEntity->getVisitorCount());
        self::assertSame('search', $savedEntity->getSourceType());
        self::assertSame('pc', $savedEntity->getDevice());
        self::assertSame('local', $savedEntity->getAreaScope());
    }

    public function testControllerInstantiation(): void
    {
        $controller = new FactTrafficTrendCrudController();
        // 验证控制器的核心功能
        self::assertSame(FactTrafficTrend::class, $controller::getEntityFqcn());
        self::assertTrue(class_exists(FactTrafficTrendCrudController::class));
    }

    public function testValidationErrors(): void
    {
        $entity = new FactTrafficTrend();

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        // 检查必填字段的验证错误 - should not be blank
        self::assertGreaterThan(0, count($violations));

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        // 验证siteId必填约束
        self::assertArrayHasKey('siteId', $violationMessages);
        // 验证date必填约束
        self::assertArrayHasKey('date', $violationMessages);
    }

    public function testInvalidChoiceValidation(): void
    {
        $entity = new FactTrafficTrend();
        $entity->setSiteId('test_site');
        $entity->setDate(new \DateTimeImmutable());
        $entity->setGran('invalid_choice'); // 无效的选择
        $entity->setDevice('invalid_device'); // 无效的设备类型

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, count($violations));

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        // 验证gran字段的Choice约束
        self::assertArrayHasKey('gran', $violationMessages);
        // 验证device字段的Choice约束
        self::assertArrayHasKey('device', $violationMessages);
    }

    public function testNegativeValueValidation(): void
    {
        $entity = new FactTrafficTrend();
        $entity->setSiteId('test_site');
        $entity->setDate(new \DateTimeImmutable());
        $entity->setGran('day');
        $entity->setPvCount(-1); // 负数值
        $entity->setVisitCount(-10); // 负数值
        $entity->setBounceRatio('150.00'); // 超出范围的值

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, count($violations));

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        // 验证PositiveOrZero约束
        self::assertArrayHasKey('pvCount', $violationMessages);
        self::assertArrayHasKey('visitCount', $violationMessages);
        // 验证Range约束
        self::assertArrayHasKey('bounceRatio', $violationMessages);
    }
}
