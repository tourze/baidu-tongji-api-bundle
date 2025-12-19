<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;
use Tourze\BaiduTongjiApiBundle\Repository\FactTrafficTrendRepository;
use Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository;
use Tourze\BaiduTongjiApiBundle\Service\TongjiApiClient;
use Tourze\BaiduTongjiApiBundle\Service\TongjiReportSyncService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiReportSyncService::class)]
#[RunTestsInSeparateProcesses]
final class TongjiReportSyncServiceTest extends AbstractIntegrationTestCase
{
    private TongjiReportSyncService $syncService;

    private RawTongjiReportRepository $rawReportRepo;

    private FactTrafficTrendRepository $factTrendRepo;

    private MockHttpClient $mockHttpClient;

    public function testSyncServiceExists(): void
    {
        $mockResponse = new MockResponse('{}');
        $mockHttpClient = new MockHttpClient($mockResponse);

        $container = self::getContainer();
        $logger = $container->get('logger');

        $mockApiClient = new TongjiApiClient($mockHttpClient, $logger);
        $container->set(TongjiApiClient::class, $mockApiClient);

        $syncService = self::getService(TongjiReportSyncService::class);

        $this->assertInstanceOf(TongjiReportSyncService::class, $syncService);
    }

    public function testSyncTrendTimeReport(): void
    {
        $config = $this->createTestConfig();
        $user = $this->createTestUser($config);

        $siteId = 'test_site_123';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');

        $mockResponseData = $this->getTestApiResponseData();
        $this->setupMockHttpClient($mockResponseData);

        $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate);

        $rawReports = $this->rawReportRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(1, $rawReports);

        $rawReport = $rawReports[0];
        $this->assertSame($siteId, $rawReport->getSiteId());
        $this->assertSame('trend/time/a', $rawReport->getMethod());
        $this->assertEquals($startDate, $rawReport->getStartDate());
        $this->assertEquals($endDate, $rawReport->getEndDate());
        $this->assertNotNull($rawReport->getParamsJson());
        $this->assertSame('pv_count,visit_count,visitor_count,ip_count,bounce_ratio,avg_visit_time,avg_visit_pages,trans_count,trans_ratio', $rawReport->getMetrics());
        $this->assertSame($mockResponseData, $rawReport->getDataJson());

        $factTrends = $this->factTrendRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(3, $factTrends);

        $factTrend = $this->findFactTrendByDate($factTrends, '2024-01-01');
        $this->assertNotNull($factTrend);
        $this->assertSame($siteId, $factTrend->getSiteId());
        $this->assertSame('day', $factTrend->getGran());
        $this->assertSame(1000, $factTrend->getPvCount());
        $this->assertSame(800, $factTrend->getVisitCount());
        $this->assertSame(600, $factTrend->getVisitorCount());
        $this->assertSame(580, $factTrend->getIpCount());
        $this->assertSame('45.50', $factTrend->getBounceRatio());
        $this->assertSame(180, $factTrend->getAvgVisitTime());
        $this->assertSame('2.50', $factTrend->getAvgVisitPages());
        $this->assertSame(15, $factTrend->getTransCount());
        $this->assertSame('1.88', $factTrend->getTransRatio());

        $factTrend = $this->findFactTrendByDate($factTrends, '2024-01-02');
        $this->assertNotNull($factTrend);
        $this->assertSame(1200, $factTrend->getPvCount());
        $this->assertSame(950, $factTrend->getVisitCount());

        $factTrend = $this->findFactTrendByDate($factTrends, '2024-01-03');
        $this->assertNotNull($factTrend);
        $this->assertSame(900, $factTrend->getPvCount());
        $this->assertSame(720, $factTrend->getVisitCount());
    }

    public function testSyncTrendTimeReportWithDuplicateData(): void
    {
        $config = $this->createTestConfig();
        $user = $this->createTestUser($config);

        $siteId = 'test_site_duplicate';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-01');

        $mockResponseData = $this->getTestApiResponseData();

        $responses = [
            new MockResponse(json_encode($mockResponseData), ['http_code' => 200]),
            new MockResponse(json_encode($mockResponseData), ['http_code' => 200]),
        ];

        $mockHttpClient = new MockHttpClient($responses);
        $container = self::getContainer();
        $logger = $container->get('logger');
        $mockApiClient = new TongjiApiClient($mockHttpClient, $logger);
        $container->set(TongjiApiClient::class, $mockApiClient);

        $this->syncService = self::getService(TongjiReportSyncService::class);

        $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate);

        $rawReportsAfterFirst = $this->rawReportRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(1, $rawReportsAfterFirst);

        $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate);

        $rawReportsAfterSecond = $this->rawReportRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(1, $rawReportsAfterSecond);

        $this->assertSame($rawReportsAfterFirst[0]->getId(), $rawReportsAfterSecond[0]->getId());
    }

    public function testSyncTrendTimeReportWithEmptyData(): void
    {
        $config = $this->createTestConfig();
        $user = $this->createTestUser($config);

        $siteId = 'test_site_empty';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-01');

        $emptyResponseData = [
            'result' => [
                'fields' => [],
                'items' => [],
            ],
        ];

        $this->setupMockHttpClient($emptyResponseData);

        $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate);

        $rawReports = $this->rawReportRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(1, $rawReports);

        $factTrends = $this->factTrendRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(0, $factTrends);
    }

    public function testSyncTrendTimeReportWithInvalidDateFormat(): void
    {
        $config = $this->createTestConfig();
        $user = $this->createTestUser($config);

        $siteId = 'test_site_invalid_date';
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-01');

        $invalidDateResponse = [
            'result' => [
                'fields' => ['start_date', 'pv_count', 'visit_count', 'visitor_count'],
                'items' => [
                    [['invalid-date-format'], 1000, 800, 600],
                    [['2024-01-01'], 1200, 950, 720],
                ],
            ],
        ];

        $this->setupMockHttpClient($invalidDateResponse);

        $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate);

        $factTrends = $this->factTrendRepo->findBy(['siteId' => $siteId]);
        $this->assertCount(1, $factTrends);

        $factTrend = $factTrends[0];
        $this->assertEquals(new \DateTimeImmutable('2024-01-01'), $factTrend->getDate());
        $this->assertSame(1200, $factTrend->getPvCount());
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();

        $this->rawReportRepo = $container->get(RawTongjiReportRepository::class);
        $this->factTrendRepo = $container->get(FactTrafficTrendRepository::class);

        $mockResponse = new MockResponse('{}');
        $this->mockHttpClient = new MockHttpClient($mockResponse);
    }

    private function createTestConfig(): BaiduOAuth2Config
    {
        $config = new BaiduOAuth2Config();
        $reflection = new \ReflectionClass($config);

        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientIdProperty->setValue($config, 'test_client_id');

        $clientSecretProperty = $reflection->getProperty('clientSecret');
        $clientSecretProperty->setAccessible(true);
        $clientSecretProperty->setValue($config, 'test_client_secret');

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($config);
        $entityManager->flush();

        return $config;
    }

    private function createTestUser(BaiduOAuth2Config $config): BaiduOAuth2User
    {
        $user = new BaiduOAuth2User();
        $user->setBaiduUid('test_user_123');
        $user->setAccessToken('test_access_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function getTestApiResponseData(): array
    {
        return [
            'result' => [
                'fields' => [
                    'start_date', 'pv_count', 'visit_count', 'visitor_count',
                    'ip_count', 'bounce_ratio', 'avg_visit_time', 'avg_visit_pages',
                    'trans_count', 'trans_ratio',
                ],
                'items' => [
                    [['2024-01-01'], 1000, 800, 600, 580, '45.50', 180, '2.50', 15, '1.88'],
                    [['2024-01-02'], 1200, 950, 720, 690, '42.10', 200, '2.80', 18, '1.89'],
                    [['2024-01-03'], 900, 720, 550, 520, '48.60', 165, '2.30', 12, '1.67'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $responseData
     */
    private function setupMockHttpClient(array $responseData): void
    {
        $container = self::getContainer();

        $mockResponse = new MockResponse(
            json_encode($responseData, JSON_THROW_ON_ERROR),
            [
                'http_code' => 200,
                'response_headers' => ['Content-Type' => 'application/json'],
            ]
        );

        $mockHttpClient = new MockHttpClient($mockResponse);
        $logger = $container->get('logger');

        $mockApiClient = new TongjiApiClient($mockHttpClient, $logger);

        $container->set(TongjiApiClient::class, $mockApiClient);

        $this->syncService = self::getService(TongjiReportSyncService::class);
    }

    /**
     * @param FactTrafficTrend[] $factTrends
     */
    private function findFactTrendByDate(array $factTrends, string $dateStr): ?FactTrafficTrend
    {
        $targetDate = new \DateTimeImmutable($dateStr);

        foreach ($factTrends as $trend) {
            $trendDate = $trend->getDate();
            if (null !== $trendDate && $trendDate->format('Y-m-d') === $targetDate->format('Y-m-d')) {
                return $trend;
            }
        }

        return null;
    }
}
