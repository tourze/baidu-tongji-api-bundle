<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
    private HttpClientInterface $httpClient;

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

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

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

        $response = new TestResponse(false !== json_encode($errorResponse) ? json_encode($errorResponse) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $this->apiClient->getSiteList($this->user);
    }

    public function testGetSiteListInvalidJson(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON response from API/');

        $response = new TestResponse('invalid json', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

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

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $params = [
            'site_id' => '12345',
            'method' => 'trend/time/a',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count,visit_count',
        ];

        $result = $this->apiClient->getReportData($this->user, $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetTrendTimeReport(): void
    {
        $responseData = ['result' => ['fields' => ['date', 'pv_count'], 'items' => []]];

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $result = $this->apiClient->getTrendTimeReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetRealtimeVisitorsReport(): void
    {
        $responseData = ['result' => ['fields' => ['area', 'visit_time'], 'items' => []]];

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $params = ['metrics' => 'area,visit_time'];
        $result = $this->apiClient->getRealtimeVisitorsReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetSourceAllReport(): void
    {
        $responseData = ['result' => ['fields' => ['source', 'pv_count'], 'items' => []]];

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $result = $this->apiClient->getSourceAllReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    public function testGetVisitToppageReport(): void
    {
        $responseData = ['result' => ['fields' => ['url', 'pv_count'], 'items' => []]];

        $response = new TestResponse(false !== json_encode($responseData) ? json_encode($responseData) : '', 200);
        /** @var TestHttpClient $httpClient */
        $httpClient = $this->httpClient;
        $httpClient->setExpectedResponse($response);

        $params = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'metrics' => 'pv_count',
        ];

        $result = $this->apiClient->getVisitToppageReport($this->user, '12345', $params);
        $this->assertSame($responseData, $result);
    }

    protected function onSetUp(): void
    {
        // 使用TestHttpClient替代匿名类
        $this->httpClient = new TestHttpClient();

        // 将HttpClient注入到容器中
        $container = self::getContainer();
        $container->set(HttpClientInterface::class, $this->httpClient);

        // 从容器获取被测试的服务
        /** @var TongjiApiClient $apiClient */
        $apiClient = $container->get(TongjiApiClient::class);
        $this->apiClient = $apiClient;

        $config = new BaiduOAuth2Config();
        $this->user = new BaiduOAuth2User();
        $this->user->setBaiduUid('test_uid');
        $this->user->setAccessToken('valid_token');
        $this->user->setExpiresIn(3600);
        $this->user->setConfig($config);
    }
}
