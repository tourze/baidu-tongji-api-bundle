<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RawTongjiReport::class)]
final class RawTongjiReportTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $report = new RawTongjiReport();
        $report->setSiteId('site123');
        $report->setMethod('trend/time/a');
        $report->setStartDate(new \DateTimeImmutable());
        $report->setEndDate(new \DateTimeImmutable('+1 day'));
        $report->setResponseHash('hash123');
        $report->setFetchedAt(new \DateTimeImmutable());

        return $report;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'paramsJson' => ['paramsJson', ['gran' => 'day']];
        yield 'metrics' => ['metrics', 'pv_count,visit_count'];
        yield 'dataJson' => ['dataJson', [['item1' => 'value1']]];
        yield 'processedAt' => ['processedAt', new \DateTimeImmutable()];
        yield 'syncStatus' => ['syncStatus', 1];
        yield 'errorMessage' => ['errorMessage', 'Test error'];
    }

    public function testConstructor(): void
    {
        $siteId = '12345';
        $method = 'trend/time/a';
        $startDate = new \DateTimeImmutable('2023-01-01');
        $endDate = new \DateTimeImmutable('2023-01-31');
        $responseHash = 'abc123';

        $report = new RawTongjiReport();
        $report->setSiteId($siteId);
        $report->setMethod($method);
        $report->setStartDate($startDate);
        $report->setEndDate($endDate);
        $report->setResponseHash($responseHash);
        $report->setFetchedAt(new \DateTimeImmutable());

        $this->assertSame($siteId, $report->getSiteId());
        $this->assertSame($method, $report->getMethod());
        $this->assertEquals($startDate, $report->getStartDate());
        $this->assertEquals($endDate, $report->getEndDate());
        $this->assertSame($responseHash, $report->getResponseHash());
        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getFetchedAt());
    }

    public function testConstructorWithDateTimeInterface(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');

        $report = new RawTongjiReport();
        $report->setSiteId('12345');
        $report->setMethod('method');
        $report->setStartDate($startDate);
        $report->setEndDate($endDate);
        $report->setResponseHash('hash');
        $report->setFetchedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getStartDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $report->getEndDate());
        $this->assertSame('2023-01-01', $report->getStartDate()->format('Y-m-d'));
        $this->assertSame('2023-01-31', $report->getEndDate()->format('Y-m-d'));
    }

    public function testSettersAndGetters(): void
    {
        $report = new RawTongjiReport();
        $report->setSiteId('site');
        $report->setMethod('method');
        $report->setStartDate(new \DateTimeImmutable());
        $report->setEndDate(new \DateTimeImmutable());
        $report->setResponseHash('hash');
        $report->setFetchedAt(new \DateTimeImmutable());

        $params = ['gran' => 'day', 'source' => 'through'];
        $report->setParamsJson($params);
        $this->assertSame($params, $report->getParamsJson());

        $metrics = 'pv_count,visit_count';
        $report->setMetrics($metrics);
        $this->assertSame($metrics, $report->getMetrics());

        $data = ['result' => ['items' => []]];
        $report->setDataJson($data);
        $this->assertSame($data, $report->getDataJson());
    }

    public function testNullableFields(): void
    {
        $report = new RawTongjiReport();
        $report->setSiteId('site');
        $report->setMethod('method');
        $report->setStartDate(new \DateTimeImmutable());
        $report->setEndDate(new \DateTimeImmutable());
        $report->setResponseHash('hash');
        $report->setFetchedAt(new \DateTimeImmutable());

        $this->assertNull($report->getParamsJson());
        $this->assertNull($report->getMetrics());
        $this->assertNull($report->getDataJson());

        $report->setParamsJson(null);
        $report->setMetrics(null);
        $report->setDataJson(null);

        $this->assertNull($report->getParamsJson());
        $this->assertNull($report->getMetrics());
        $this->assertNull($report->getDataJson());
    }
}
