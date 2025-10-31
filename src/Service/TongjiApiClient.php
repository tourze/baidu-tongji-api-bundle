<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;

#[WithMonologChannel(channel: 'baidu_tongji_api')]
class TongjiApiClient
{
    private const BASE_URL = 'https://openapi.baidu.com/rest/2.0/tongji';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws TongjiApiException
     */
    public function getSiteList(BaiduOAuth2User $user): array
    {
        if ($user->isTokenExpired()) {
            throw TongjiApiException::accessTokenExpired();
        }

        $url = self::BASE_URL . '/config/getSiteList';

        $this->logger->info('Requesting site list from Baidu Tongji API', [
            'user_id' => $user->getBaiduUid(),
            'url' => $url,
        ]);

        $response = $this->httpClient->request('GET', $url, [
            'query' => [
                'access_token' => $user->getAccessToken(),
            ],
            'timeout' => 30,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * 获取报告数据统一接口
     *
     * @param array<string, mixed> $params 请求参数
     * @return array<string, mixed>
     * @throws TongjiApiException
     */
    public function getReportData(BaiduOAuth2User $user, array $params): array
    {
        if ($user->isTokenExpired()) {
            throw TongjiApiException::accessTokenExpired();
        }

        $url = self::BASE_URL . '/report/getData';

        $query = array_merge([
            'access_token' => $user->getAccessToken(),
        ], $params);

        $this->logger->info('Requesting report data from Baidu Tongji API', [
            'user_id' => $user->getBaiduUid(),
            'url' => $url,
            'method' => $params['method'] ?? 'unknown',
        ]);

        $response = $this->httpClient->request('GET', $url, [
            'query' => $query,
            'timeout' => 30,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * 趋势分析 trend/time/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics, gran?, source?, clientDevice?, area?)
     * @return array<string, mixed>
     */
    public function getTrendTimeReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'trend/time/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 实时访客 trend/latest/a
     *
     * @param array<string, mixed> $params 请求参数 (metrics, order?, max_results?, source?, area?)
     * @return array<string, mixed>
     */
    public function getRealtimeVisitorsReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'trend/latest/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 推广方式 pro/product/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getProProductReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'pro/product/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 百度推广趋势 pro/hour/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getProHourReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'pro/hour/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 全部来源 source/all/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics, viewType?, clientDevice?, visitor?)
     * @return array<string, mixed>
     */
    public function getSourceAllReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'source/all/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 搜索引擎 source/engine/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getSourceEngineReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'source/engine/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 搜索词 source/searchword/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getSourceSearchwordReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'source/searchword/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 外部链接 source/link/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getSourceLinkReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'source/link/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 指定广告跟踪 custom/media/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics, flag?)
     * @return array<string, mixed>
     */
    public function getCustomMediaReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'custom/media/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 受访页面 visit/toppage/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getVisitToppageReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'visit/toppage/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 入口页面 visit/landingpage/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getVisitLandingpageReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'visit/landingpage/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 受访域名 visit/topdomain/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getVisitTopdomainReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'visit/topdomain/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 地域分布(中国) visit/district/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getVisitDistrictReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'visit/district/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 世界地域分布 visit/world/a
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getVisitWorldReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'visit/world/a',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 网站概况(趋势数据) overview/getTimeTrendRpt
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics, gran?)
     * @return array<string, mixed>
     */
    public function getOverviewTimeTrendReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'overview/getTimeTrendRpt',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 网站概况(地域分布) overview/getDistrictRpt
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getOverviewDistrictReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'overview/getDistrictRpt',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * 网站概况(常用轨迹) overview/getCommonTrackRpt
     *
     * @param array<string, mixed> $params 请求参数 (start_date, end_date, metrics)
     * @return array<string, mixed>
     */
    public function getOverviewCommonTrackReport(BaiduOAuth2User $user, string $siteId, array $params): array
    {
        $requestParams = array_merge([
            'site_id' => $siteId,
            'method' => 'overview/getCommonTrackRpt',
        ], $params);

        return $this->getReportData($user, $requestParams);
    }

    /**
     * @return array<string, mixed>
     * @throws TongjiApiException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        $this->logger->debug('API response received', [
            'status_code' => $statusCode,
            'response_size' => strlen($content),
        ]);

        if (200 !== $statusCode) {
            $this->logger->error('API request failed', [
                'status_code' => $statusCode,
                'response' => $content,
            ]);
            throw TongjiApiException::httpError("API request failed with status {$statusCode}");
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw TongjiApiException::invalidResponse('Invalid JSON response from API: ' . $e->getMessage());
        }

        if (!is_array($data)) {
            throw TongjiApiException::invalidResponse('API response is not a valid array');
        }

        // Validate that all keys are strings to match return type
        $validatedData = $this->validateResponseStructure($data);

        if (isset($validatedData['error_code'])) {
            $errorCode = $validatedData['error_code'];
            $errorMsg = $validatedData['error_msg'] ?? 'Unknown error';

            // Ensure error_code is string for safe concatenation
            $errorCodeStr = is_scalar($errorCode) ? (string) $errorCode : 'unknown';
            $errorMsgStr = is_scalar($errorMsg) ? (string) $errorMsg : 'Unknown error';

            $this->logger->error('API returned error', [
                'error_code' => $errorCode,
                'error_msg' => $errorMsgStr,
            ]);
            throw TongjiApiException::apiError("Error {$errorCodeStr}: {$errorMsgStr}");
        }

        return $validatedData;
    }

    /**
     * 验证并转换响应数据，确保键为字符串类型
     *
     * @param array<mixed, mixed> $data
     * @return array<string, mixed>
     */
    private function validateResponseStructure(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // 将所有键转换为字符串以匹配返回类型
            $stringKey = (string) $key;
            $result[$stringKey] = $value;
        }

        return $result;
    }
}
