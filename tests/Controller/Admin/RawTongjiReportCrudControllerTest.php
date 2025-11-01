<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\RawTongjiReportCrudController;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 百度统计原始报告CRUD控制器测试
 *
 * @internal
 */
#[CoversClass(RawTongjiReportCrudController::class)]
#[RunTestsInSeparateProcesses]
final class RawTongjiReportCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getEntityFqcn(): string
    {
        return RawTongjiReport::class;
    }

    protected function getControllerService(): RawTongjiReportCrudController
    {
        return self::getService(RawTongjiReportCrudController::class);
    }

    /**
     * 提供索引页面的表头数据
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '站点ID' => ['站点ID'];
        yield '报告方法' => ['报告方法'];
        yield '开始日期' => ['开始日期'];
        yield '结束日期' => ['结束日期'];
        yield '拉取时间' => ['拉取时间'];
    }

    /**
     * 创建页面需要用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'method' => ['method'];
        yield 'startDate' => ['startDate'];
        yield 'endDate' => ['endDate'];
        yield 'responseHash' => ['responseHash'];
    }

    /**
     * 编辑页用到的字段
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'siteId' => ['siteId'];
        yield 'method' => ['method'];
        yield 'startDate' => ['startDate'];
        yield 'endDate' => ['endDate'];
        yield 'metrics' => ['metrics'];
        yield 'syncStatus' => ['syncStatus'];
    }

    public function testGetEntityFqcn(): void
    {
        self::assertSame(RawTongjiReport::class, RawTongjiReportCrudController::getEntityFqcn());
    }

    public function testConfigureCrud(): void
    {
        $controller = new RawTongjiReportCrudController();
        // 使用真实的Crud实例，而不是Mock
        $crud = $controller->configureCrud(Crud::new());

        // 验证配置包含自定义的标签设置
        $crudDto = $crud->getAsDto();
        self::assertSame('百度统计原始报告', $crudDto->getEntityLabelInSingular());
    }

    public function testConfigureActions(): void
    {
        $controller = new RawTongjiReportCrudController();
        // 使用 Actions::new() 创建实际实例，避免 final 类 Mock 问题
        $actions = $controller->configureActions(Actions::new());

        // 验证是否包含基本的页面actions
        self::assertNotEmpty($actions->getAsDto('index')->getActions());
    }

    public function testConfigureFilters(): void
    {
        $controller = new RawTongjiReportCrudController();
        // 使用 Filters::new() 创建实际实例，避免 final 类 Mock 问题
        $filters = $controller->configureFilters(Filters::new());

        // 验证过滤器配置返回 Filters 对象（已验证返回类型）
        self::assertIsObject($filters);
    }

    public function testConfigureFields(): void
    {
        $controller = new RawTongjiReportCrudController();
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
        $entity = new RawTongjiReport();
        $entity->setSiteId('test_site_456');
        $entity->setMethod('trend/time');
        $entity->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $entity->setEndDate(new \DateTimeImmutable('2024-01-31'));
        $entity->setResponseHash(md5('test_response_data'));
        $entity->setFetchedAt(new \DateTimeImmutable());
        $entity->setMetrics('pv_count,visitor_count');
        $entity->setParamsJson(['gran' => 'day', 'metrics' => 'pv_count,visitor_count']);
        $entity->setDataJson(['result' => [['data' => [100, 50]]]]);
        $entity->setSyncStatus(0);

        $entityManager->persist($entity);
        $entityManager->flush();

        // 验证实体是否正确保存
        /** @var EntityRepository<RawTongjiReport> $repository */
        $repository = $entityManager->getRepository(RawTongjiReport::class);
        $savedEntity = $repository->find($entity->getId());

        self::assertNotNull($savedEntity);
        self::assertSame('test_site_456', $savedEntity->getSiteId());
        self::assertSame('trend/time', $savedEntity->getMethod());
        $startDate = $savedEntity->getStartDate();
        self::assertNotNull($startDate);
        self::assertSame('2024-01-01', $startDate->format('Y-m-d'));
        $endDate = $savedEntity->getEndDate();
        self::assertNotNull($endDate);
        self::assertSame('2024-01-31', $endDate->format('Y-m-d'));
        self::assertSame('pv_count,visitor_count', $savedEntity->getMetrics());
        self::assertNotNull($savedEntity->getFetchedAt());
    }

    public function testControllerInstantiation(): void
    {
        $controller = new RawTongjiReportCrudController();
        // 验证控制器的核心功能
        self::assertSame(RawTongjiReport::class, $controller::getEntityFqcn());
        self::assertTrue(class_exists(RawTongjiReportCrudController::class));
    }

    public function testValidationErrors(): void
    {
        $entity = new RawTongjiReport();

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
        self::assertArrayHasKey('method', $violationMessages);
        self::assertArrayHasKey('startDate', $violationMessages);
        self::assertArrayHasKey('endDate', $violationMessages);
        self::assertArrayHasKey('responseHash', $violationMessages);
        self::assertArrayHasKey('fetchedAt', $violationMessages);
    }

    public function testLengthValidation(): void
    {
        $entity = new RawTongjiReport();
        $entity->setSiteId(str_repeat('a', 129)); // 超过长度限制
        $entity->setMethod(str_repeat('b', 129)); // 超过长度限制
        $entity->setResponseHash(str_repeat('c', 65)); // 超过长度限制
        $entity->setStartDate(new \DateTimeImmutable());
        $entity->setEndDate(new \DateTimeImmutable());
        $entity->setFetchedAt(new \DateTimeImmutable());

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, count($violations));

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        // 验证长度约束
        self::assertArrayHasKey('siteId', $violationMessages);
        self::assertArrayHasKey('method', $violationMessages);
        self::assertArrayHasKey('responseHash', $violationMessages);
    }
}
