<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;

class TongjiSubDirectoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const SUBDIR_1_REFERENCE = 'subdir-1';
    public const SUBDIR_2_REFERENCE = 'subdir-2';

    public function load(ObjectManager $manager): void
    {
        $site1 = $this->getReference(TongjiSiteFixtures::SITE_1_REFERENCE, TongjiSite::class);

        $subdir1 = new TongjiSubDirectory();
        $subdir1->setSubDirId('subdir_001');
        $subdir1->setSubDir('/blog/');
        $subdir1->setSite($site1);
        $subdir1->setStatus(0);
        $subdir1->setSubDirCreateTime(new \DateTimeImmutable('2024-01-16'));
        $subdir1->setRawData([
            'sub_dir_id' => 'subdir_001',
            'sub_dir' => '/blog/',
            'status' => 0,
        ]);

        $manager->persist($subdir1);

        $subdir2 = new TongjiSubDirectory();
        $subdir2->setSubDirId('subdir_002');
        $subdir2->setSubDir('/news/');
        $subdir2->setSite($site1);
        $subdir2->setStatus(0);
        $subdir2->setSubDirCreateTime(new \DateTimeImmutable('2024-01-17'));
        $subdir2->setRawData([
            'sub_dir_id' => 'subdir_002',
            'sub_dir' => '/news/',
            'status' => 0,
        ]);

        $manager->persist($subdir2);

        $manager->flush();

        $this->addReference(self::SUBDIR_1_REFERENCE, $subdir1);
        $this->addReference(self::SUBDIR_2_REFERENCE, $subdir2);
    }

    public function getDependencies(): array
    {
        return [
            TongjiSiteFixtures::class,
        ];
    }
}
