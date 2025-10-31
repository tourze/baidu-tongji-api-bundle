<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;

class TongjiSiteFixtures extends Fixture
{
    public const SITE_1_REFERENCE = 'site-1';
    public const SITE_2_REFERENCE = 'site-2';

    public function load(ObjectManager $manager): void
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id_123');
        $config->setClientSecret('test_client_secret_456');
        $config->setScope('basic');
        $config->setValid(true);

        $manager->persist($config);

        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_baidu_uid_123456');
        $user->setAccessToken('test_access_token_abcdef');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setUsername('测试用户');
        $user->setAvatar('https://unsplash.com/photo-1633332755192-727a05c4013d?w=400');
        $user->setRawData(['username' => '测试用户']);

        $manager->persist($user);

        $site1 = new TongjiSite();
        $site1->setSiteId('test_site_12345');
        $site1->setDomain('demo1.unsplash.com');
        $site1->setUser($user);
        $site1->setStatus(0);
        $site1->setSiteCreateTime(new \DateTimeImmutable('2024-01-15'));
        $site1->setRawData([
            'site_id' => 'test_site_12345',
            'domain' => 'demo1.unsplash.com',
            'status' => 0,
        ]);

        $manager->persist($site1);

        $site2 = new TongjiSite();
        $site2->setSiteId('test_site_67890');
        $site2->setDomain('demo2.unsplash.com');
        $site2->setUser($user);
        $site2->setStatus(0);
        $site2->setSiteCreateTime(new \DateTimeImmutable('2024-02-20'));
        $site2->setRawData([
            'site_id' => 'test_site_67890',
            'domain' => 'demo2.unsplash.com',
            'status' => 0,
        ]);

        $manager->persist($site2);

        $manager->flush();

        $this->addReference(self::SITE_1_REFERENCE, $site1);
        $this->addReference(self::SITE_2_REFERENCE, $site2);
    }
}
