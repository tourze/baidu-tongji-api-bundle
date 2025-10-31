<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;

class FactTrafficTrendFixtures extends Fixture
{
    public const TREND_1_REFERENCE = 'trend-1';
    public const TREND_2_REFERENCE = 'trend-2';

    public function load(ObjectManager $manager): void
    {
        $trend1 = new FactTrafficTrend();
        $trend1->setSiteId('test_site_12345');
        $trend1->setDate(new \DateTimeImmutable('2024-08-01'));
        $trend1->setGran('day');
        $trend1->setSourceType('direct');
        $trend1->setDevice('pc');
        $trend1->setAreaScope('china');
        $trend1->setPvCount(12500);
        $trend1->setVisitCount(4320);
        $trend1->setVisitorCount(3200);
        $trend1->setIpCount(3100);
        $trend1->setBounceRatio('25.30');
        $trend1->setAvgVisitTime(180);
        $trend1->setAvgVisitPages('2.8');
        $trend1->setTransCount(120);
        $trend1->setTransRatio('2.78');

        $manager->persist($trend1);

        $trend2 = new FactTrafficTrend();
        $trend2->setSiteId('test_site_67890');
        $trend2->setDate(new \DateTimeImmutable('2024-08-01'));
        $trend2->setGran('day');
        $trend2->setSourceType('search');
        $trend2->setDevice('mobile');
        $trend2->setAreaScope('beijing');
        $trend2->setPvCount(8600);
        $trend2->setVisitCount(2100);
        $trend2->setVisitorCount(1800);
        $trend2->setIpCount(1750);
        $trend2->setBounceRatio('42.60');
        $trend2->setAvgVisitTime(95);
        $trend2->setAvgVisitPages('1.5');
        $trend2->setTransCount(45);
        $trend2->setTransRatio('2.14');

        $manager->persist($trend2);

        $manager->flush();

        $this->addReference(self::TREND_1_REFERENCE, $trend1);
        $this->addReference(self::TREND_2_REFERENCE, $trend2);
    }
}
