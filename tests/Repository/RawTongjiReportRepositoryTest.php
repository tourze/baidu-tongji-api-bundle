<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;
use Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RawTongjiReportRepository::class)]
#[RunTestsInSeparateProcesses]
final class RawTongjiReportRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): RawTongjiReportRepository
    {
        return self::getService(RawTongjiReportRepository::class);
    }

    protected function createNewEntity(): object
    {
        $uniqueId = uniqid('site_', true);
        $report = new RawTongjiReport();
        $report->setSiteId($uniqueId);
        $report->setMethod('trend/time/a');
        $report->setStartDate(new \DateTimeImmutable());
        $report->setEndDate(new \DateTimeImmutable('+1 day'));
        $report->setResponseHash(hash('sha256', uniqid('hash_', true)));
        $report->setFetchedAt(new \DateTimeImmutable());
        $report->setParamsJson(['gran' => 'day']);
        $report->setMetrics('pv_count');
        $report->setDataJson(['result' => ['items' => []]]);

        return $report;
    }

    protected function onSetUp(): void
    {
        // No additional setup needed
    }

    public function testSaveAndFindRawTongjiReport(): void
    {
        $report = new RawTongjiReport();
        $report->setSiteId('site123');
        $report->setMethod('trend/time/a');
        $report->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $report->setEndDate(new \DateTimeImmutable('2024-01-03'));
        $report->setResponseHash('test-hash-123');
        $report->setFetchedAt(new \DateTimeImmutable());

        $report->setParamsJson(['gran' => 'day', 'metrics' => 'pv_count']);
        $report->setMetrics('pv_count,visitor_count');
        $report->setDataJson(['result' => ['items' => [['pv_count' => [100, 200]]]]]);

        self::getEntityManager()->persist($report);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->find($report->getId());
        $this->assertNotNull($found);
        $this->assertSame('site123', $found->getSiteId());
        $this->assertSame('trend/time/a', $found->getMethod());
        $this->assertSame('test-hash-123', $found->getResponseHash());
        $this->assertSame(['gran' => 'day', 'metrics' => 'pv_count'], $found->getParamsJson());
        $this->assertSame('pv_count,visitor_count', $found->getMetrics());
    }

    public function testFindByParamsHash(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');

        $report1 = new RawTongjiReport();
        $report1->setSiteId('site123');
        $report1->setMethod('trend/time/a');
        $report1->setStartDate($startDate);
        $report1->setEndDate($endDate);
        $report1->setResponseHash('hash-1');
        $report1->setFetchedAt(new \DateTimeImmutable());

        $report2 = new RawTongjiReport();
        $report2->setSiteId('site123');
        $report2->setMethod('trend/time/a');
        $report2->setStartDate($startDate);
        $report2->setEndDate($endDate);
        $report2->setResponseHash('hash-2');
        $report2->setFetchedAt(new \DateTimeImmutable());

        $report3 = new RawTongjiReport();
        $report3->setSiteId('site456');
        $report3->setMethod('trend/time/a');
        $report3->setStartDate($startDate);
        $report3->setEndDate($endDate);
        $report3->setResponseHash('hash-1');
        $report3->setFetchedAt(new \DateTimeImmutable());

        self::getEntityManager()->persist($report1);
        self::getEntityManager()->persist($report2);
        self::getEntityManager()->persist($report3);
        self::getEntityManager()->flush();

        $found = $this->getRepository()->findByParamsHash('site123', 'trend/time/a', $startDate, $endDate, 'hash-1');
        $this->assertNotNull($found);
        $this->assertSame('site123', $found->getSiteId());
        $this->assertSame('hash-1', $found->getResponseHash());

        $notFound = $this->getRepository()->findByParamsHash('site123', 'trend/time/a', $startDate, $endDate, 'nonexistent-hash');
        $this->assertNull($notFound);
    }

    public function testGenerateResponseHash(): void
    {
        $params = ['gran' => 'day', 'metrics' => 'pv_count'];
        $responseData = ['result' => ['items' => [['pv_count' => [100, 200]]]]];

        $hash1 = $this->getRepository()->generateResponseHash($params, $responseData);
        $hash2 = $this->getRepository()->generateResponseHash($params, $responseData);

        $this->assertSame($hash1, $hash2, '相同参数和数据应生成相同哈希');
        $this->assertSame(64, strlen($hash1), '哈希长度应为64字符（SHA256）');

        $differentParams = ['gran' => 'hour', 'metrics' => 'pv_count'];
        $hash3 = $this->getRepository()->generateResponseHash($differentParams, $responseData);

        $this->assertNotSame($hash1, $hash3, '不同参数应生成不同哈希');
    }

    public function testGenerateResponseHashWithInvalidJson(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessage('Failed to encode data to JSON');

        // Create invalid data that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        $invalidData = ['resource' => $resource];

        $this->getRepository()->generateResponseHash([], $invalidData);
    }
}
