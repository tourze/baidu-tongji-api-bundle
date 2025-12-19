<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Psr\Log\LoggerInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;
use Tourze\BaiduTongjiApiBundle\Service\TongjiApiClient;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiApiClient::class)]
#[RunTestsInSeparateProcesses]
final class TongjiApiClientTest extends AbstractIntegrationTestCase
{
    private HttpClientInterface&MockObject $httpClient;

    private LoggerInterface&MockObject $logger;

    private TongjiApiClient $apiClient;

    private BaiduOAuth2User $user;

    public function testGetSiteListSuccess(): void
    {
        $responseData = [
            'list' => [
                [
                    'site_id' => '12345',
                    'domain' => 'example.com',
                    'status' => 0,
                    'create_time' => 1234567890,
                    'sub_dir_list' => [
                        [
                            'sub_dir_id' => 'sub123',
                            'sub_dir' => '/blog',
                            'status' => 0,
                            'create_time' => 1234567891,
                        ],
                    ],
                ],
            ],
        ];

        // 创建Mock响应对象
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        // 配置HTTP客户端Mock
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://openapi.baidu.com/rest/2.0/tongji/config/getSiteList', [
                'query' => [
                    'access_token' => 'valid_token',
                ],
                'timeout' => 30,
            ])
            ->willReturn($response);

        $result = $this->apiClient->getSiteList($this->user);

        $this->assertSame($responseData, $result);
    }

    public function testGetSiteListWithExpiredToken(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessage('Access token expired');

        $config = new BaiduOAuth2Config();
        $expiredUser = new BaiduOAuth2User();
        $expiredUser->setBaiduUid('test_uid');
        $expiredUser->setAccessToken('expired_token');
        $expiredUser->setExpiresIn(-1);
        $expiredUser->setConfig($config);

        // 使用已经在 onSetUp 中设置的 apiClient
        $this->apiClient->getSiteList($expiredUser);
    }

    public function testGetSiteListApiError(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessage('Baidu Tongji API error: Error 110: Invalid access token');

        $errorResponse = [
            'error_code' => 110,
            'error_msg' => 'Invalid access token',
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($errorResponse));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->apiClient->getSiteList($this->user);
    }

    public function testGetSiteListInvalidJson(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON response from API/');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn('invalid json');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->apiClient->getSiteList($this->user);
    }

    public function testGetReportDataSuccess(): void
    {
        $responseData = [
            'result' => [
                'fields' => ['date', 'pv_count', 'visit_count'],
                'items' => [
                    [['2023-01-01'], 1000, 500],
                    [['2023-01-02'], 1200, 600],
                ],
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        $params = [
            'site_id' => '12345',
            'method' => 'trend/time/a',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count,visit_count',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://openapi.baidu.com/rest/2.0/tongji/report/getData', [
                'query' => array_merge(['access_token' => 'valid_token'], $params),
                'timeout' => 30,
            ])
            ->willReturn($response);

        $result = $this->apiClient->getReportData($this->user, $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetTrendTimeReport(): void
    {
        $responseData = ['result' => ['fields' => ['date', 'pv_count'], 'items' => []]];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $expectedParams = array_merge([
            'site_id' => '12345',
            'method' => 'trend/time/a',
        ], $params);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://openapi.baidu.com/rest/2.0/tongji/report/getData', [
                'query' => array_merge(['access_token' => 'valid_token'], $expectedParams),
                'timeout' => 30,
            ])
            ->willReturn($response);

        $result = $this->apiClient->getTrendTimeReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetRealtimeVisitorsReport(): void
    {
        $responseData = ['result' => ['fields' => ['area', 'visit_time'], 'items' => []]];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        $params = ['metrics' => 'area,visit_time'];

        $expectedParams = array_merge([
            'site_id' => '12345',
            'method' => 'trend/latest/a',
        ], $params);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->apiClient->getRealtimeVisitorsReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetSourceAllReport(): void
    {
        $responseData = ['result' => ['fields' => ['source', 'pv_count'], 'items' => []]];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $expectedParams = array_merge([
            'site_id' => '12345',
            'method' => 'source/all/a',
        ], $params);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->apiClient->getSourceAllReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetVisitToppageReport(): void
    {
        $responseData = ['result' => ['fields' => ['url', 'pv_count'], 'items' => []]];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn(json_encode($responseData));

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $expectedParams = array_merge([
            'site_id' => '12345',
            'method' => 'visit/toppage/a',
        ], $params);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->apiClient->getVisitToppageReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    protected function onSetUp(): void
    {
        // 创建HTTP客户端Mock
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        // 创建Logger Mock
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将Mock注入容器，替换测试框架的默认MockHttpClient
        $container = self::getContainer();
        $container->set(HttpClientInterface::class, $this->httpClient);
        $container->set('http_client', $this->httpClient);
        // 为带channel的logger设置Mock
        $container->set('monolog.logger.baidu_tongji_api', $this->logger);

        // 从容器获取被测试的服务实例
        $this->apiClient = self::getService(TongjiApiClient::class);

        $config = new BaiduOAuth2Config();
        $this->user = new BaiduOAuth2User();
        $this->user->setBaiduUid('test_uid');
        $this->user->setAccessToken('valid_token');
        $this->user->setExpiresIn(3600);
        $this->user->setConfig($config);
    }
}
