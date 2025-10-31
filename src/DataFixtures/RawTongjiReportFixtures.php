<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;

class RawTongjiReportFixtures extends Fixture
{
    public const REPORT_1_REFERENCE = 'report-1';
    public const REPORT_2_REFERENCE = 'report-2';

    public function load(ObjectManager $manager): void
    {
        $report1 = new RawTongjiReport();
        $report1->setSiteId('test_site_12345');
        $report1->setMethod('overview/getTimeTrendRpt');
        $report1->setStartDate(new \DateTimeImmutable('2024-08-01'));
        $report1->setEndDate(new \DateTimeImmutable('2024-08-07'));
        $report1->setResponseHash('hash_abc123def456');
        $report1->setFetchedAt(new \DateTimeImmutable());
        $report1->setParamsJson([
            'gran' => 'day',
            'metrics' => 'pv_count,visit_count,visitor_count',
        ]);
        $report1->setMetrics('pv_count,visit_count,visitor_count');
        $report1->setDataJson([
            'header' => ['date', 'pv_count', 'visit_count', 'visitor_count'],
            'body' => [
                ['20240801', '12500', '4320', '3200'],
                ['20240802', '11800', '4120', '3100'],
            ],
        ]);

        $manager->persist($report1);

        $report2 = new RawTongjiReport();
        $report2->setSiteId('test_site_67890');
        $report2->setMethod('source/engine/getRpt');
        $report2->setStartDate(new \DateTimeImmutable('2024-08-01'));
        $report2->setEndDate(new \DateTimeImmutable('2024-08-01'));
        $report2->setResponseHash('hash_xyz789abc123');
        $report2->setFetchedAt(new \DateTimeImmutable());
        $report2->setParamsJson([
            'device' => 'mobile',
            'source' => 'engine',
        ]);
        $report2->setMetrics('pv_count,visit_count');
        $report2->setDataJson([
            'header' => ['search_engine', 'pv_count', 'visit_count'],
            'body' => [
                ['baidu', '8600', '2100'],
                ['google', '3200', '780'],
            ],
        ]);

        $manager->persist($report2);

        $manager->flush();

        $this->addReference(self::REPORT_1_REFERENCE, $report1);
        $this->addReference(self::REPORT_2_REFERENCE, $report2);
    }
}
