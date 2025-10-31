<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(FactTrafficTrend::class)]
final class FactTrafficTrendTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $entity = new FactTrafficTrend();
        $entity->setSiteId('site123');
        $entity->setDate(new \DateTimeImmutable());
        $entity->setGran('day');

        return $entity;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'sourceType' => ['sourceType', 'search'];
        yield 'device' => ['device', 'mobile'];
        yield 'areaScope' => ['areaScope', 'china'];
        yield 'pvCount' => ['pvCount', 1000];
        yield 'visitCount' => ['visitCount', 500];
        yield 'visitorCount' => ['visitorCount', 300];
        yield 'ipCount' => ['ipCount', 250];
        yield 'bounceRatio' => ['bounceRatio', '45.67'];
        yield 'avgVisitTime' => ['avgVisitTime', 120];
        yield 'avgVisitPages' => ['avgVisitPages', '2.5'];
    }

    public function testConstructor(): void
    {
        $siteId = '12345';
        $date = new \DateTimeImmutable('2023-01-01');
        $gran = 'day';

        $fact = new FactTrafficTrend();
        $fact->setSiteId($siteId);
        $fact->setDate($date);
        $fact->setGran($gran);

        $this->assertSame($siteId, $fact->getSiteId());
        $this->assertEquals($date, $fact->getDate());
        $this->assertSame($gran, $fact->getGran());
    }

    public function testConstructorWithDateTimeInterface(): void
    {
        $date = new \DateTime('2023-01-01');
        $fact = new FactTrafficTrend();
        $fact->setSiteId('site');
        $fact->setDate($date);
        $fact->setGran('day');

        $this->assertInstanceOf(\DateTimeImmutable::class, $fact->getDate());
        $this->assertSame('2023-01-01', $fact->getDate()->format('Y-m-d'));
    }

    public function testDimensionSetters(): void
    {
        $fact = new FactTrafficTrend();
        $fact->setSiteId('site');
        $fact->setDate(new \DateTimeImmutable());
        $fact->setGran('day');

        $fact->setSourceType('search');
        $this->assertSame('search', $fact->getSourceType());

        $fact->setDevice('mobile');
        $this->assertSame('mobile', $fact->getDevice());

        $fact->setAreaScope('china');
        $this->assertSame('china', $fact->getAreaScope());
    }

    public function testMetricSetters(): void
    {
        $fact = new FactTrafficTrend();
        $fact->setSiteId('site');
        $fact->setDate(new \DateTimeImmutable());
        $fact->setGran('day');

        $fact->setPvCount(1000);
        $this->assertSame(1000, $fact->getPvCount());

        $fact->setVisitCount(500);
        $this->assertSame(500, $fact->getVisitCount());

        $fact->setVisitorCount(300);
        $this->assertSame(300, $fact->getVisitorCount());

        $fact->setIpCount(250);
        $this->assertSame(250, $fact->getIpCount());

        $fact->setBounceRatio('45.67');
        $this->assertSame('45.67', $fact->getBounceRatio());

        $fact->setAvgVisitTime(120);
        $this->assertSame(120, $fact->getAvgVisitTime());

        $fact->setAvgVisitPages('2.5');
        $this->assertSame('2.5', $fact->getAvgVisitPages());

        $fact->setTransCount(10);
        $this->assertSame(10, $fact->getTransCount());

        $fact->setTransRatio('2.0');
        $this->assertSame('2.0', $fact->getTransRatio());
    }

    public function testNullableFields(): void
    {
        $fact = new FactTrafficTrend();
        $fact->setSiteId('site');
        $fact->setDate(new \DateTimeImmutable());
        $fact->setGran('day');

        $this->assertNull($fact->getSourceType());
        $this->assertNull($fact->getDevice());
        $this->assertNull($fact->getAreaScope());
        $this->assertNull($fact->getPvCount());
        $this->assertNull($fact->getVisitCount());
        $this->assertNull($fact->getVisitorCount());
        $this->assertNull($fact->getIpCount());
        $this->assertNull($fact->getBounceRatio());
        $this->assertNull($fact->getAvgVisitTime());
        $this->assertNull($fact->getAvgVisitPages());
        $this->assertNull($fact->getTransCount());
        $this->assertNull($fact->getTransRatio());
    }
}
