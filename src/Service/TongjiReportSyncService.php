<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;
use Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository;

#[WithMonologChannel(channel: 'baidu_tongji_api')]
class TongjiReportSyncService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private TongjiApiClient $apiClient,
        private RawTongjiReportRepository $rawReportRepo,
    ) {
    }

    /**
     * 同步趋势分析报告
     *
     * @param array<string, mixed> $params
     */
    public function syncTrendTimeReport(
        BaiduOAuth2User $user,
        string $siteId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $params = [],
    ): void {
        $method = 'trend/time/a';

        $requestParams = array_merge([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'metrics' => 'pv_count,visit_count,visitor_count,ip_count,bounce_ratio,avg_visit_time,avg_visit_pages,trans_count,trans_ratio',
        ], $params);

        $this->logger->info('同步趋势分析报告', [
            'site_id' => $siteId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'params' => $params,
        ]);

        $responseData = $this->apiClient->getTrendTimeReport($user, $siteId, $requestParams);

        $responseHash = $this->rawReportRepo->generateResponseHash($requestParams, $responseData);

        $existingReport = $this->rawReportRepo->findByParamsHash(
            $siteId,
            $method,
            $startDate,
            $endDate,
            $responseHash
        );

        if (null !== $existingReport) {
            $this->logger->debug('报告数据未变化，跳过', [
                'site_id' => $siteId,
                'method' => $method,
                'response_hash' => $responseHash,
            ]);

            return;
        }

        $rawReport = new RawTongjiReport();
        $rawReport->setSiteId($siteId);
        $rawReport->setMethod($method);
        $rawReport->setStartDate($startDate);
        $rawReport->setEndDate($endDate);
        $rawReport->setResponseHash($responseHash);
        $rawReport->setFetchedAt(new \DateTimeImmutable());
        $rawReport->setParamsJson($requestParams);
        $rawReport->setMetrics(is_string($requestParams['metrics'] ?? null) ? $requestParams['metrics'] : null);
        $rawReport->setDataJson($responseData);

        $this->em->persist($rawReport);

        $this->processReportData($rawReport, $responseData);

        $this->em->flush();

        $this->logger->info('趋势分析报告同步完成', [
            'site_id' => $siteId,
            'method' => $method,
            'raw_report_id' => $rawReport->getId(),
        ]);
    }

    /**
     * 处理报告数据，转换为事实表
     *
     * @param array<string, mixed> $responseData
     */
    private function processReportData(RawTongjiReport $rawReport, array $responseData): void
    {
        $result = $responseData['result'] ?? null;
        if (!is_array($result) || !isset($result['items']) || !is_array($result['items']) || [] === $result['items']) {
            $this->logger->warning('报告数据为空', [
                'method' => $rawReport->getMethod(),
                'site_id' => $rawReport->getSiteId(),
            ]);

            return;
        }

        if ('trend/time/a' === $rawReport->getMethod()) {
            $this->processTrendTimeData($rawReport, $responseData);
        }
    }

    /**
     * 处理趋势分析数据
     *
     * @param array<string, mixed> $responseData
     */
    private function processTrendTimeData(RawTongjiReport $rawReport, array $responseData): void
    {
        $result = $responseData['result'] ?? [];
        if (!is_array($result)) {
            return;
        }

        $fields = $result['fields'] ?? [];
        $items = $result['items'] ?? [];

        if (!is_array($fields) || !is_array($items) || [] === $fields || [] === $items) {
            return;
        }

        $stringFields = array_filter($fields, 'is_string');
        if (count($stringFields) !== count($fields)) {
            return;
        }

        $fieldMap = array_flip($stringFields);

        $params = $rawReport->getParamsJson() ?? [];
        $gran = is_string($params['gran'] ?? null) ? $params['gran'] : 'day';

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $factTrend = $this->createFactTrendFromItem($item, $rawReport, $fieldMap, $params, $gran);
            if (null !== $factTrend) {
                $this->em->persist($factTrend);
            }
        }
    }

    /**
     * 设置事实表指标
     *
     * @param array<mixed> $item
     * @param array<string, int> $fieldMap
     */
    private function setFactMetrics(FactTrafficTrend $factTrend, array $item, array $fieldMap): void
    {
        $this->setMetricValue($factTrend, 'setPvCount', $item, $fieldMap, 'pv_count');
        $this->setMetricValue($factTrend, 'setVisitCount', $item, $fieldMap, 'visit_count');
        $this->setMetricValue($factTrend, 'setVisitorCount', $item, $fieldMap, 'visitor_count');
        $this->setMetricValue($factTrend, 'setIpCount', $item, $fieldMap, 'ip_count');
        $this->setMetricValue($factTrend, 'setBounceRatio', $item, $fieldMap, 'bounce_ratio');
        $this->setMetricValue($factTrend, 'setAvgVisitTime', $item, $fieldMap, 'avg_visit_time');
        $this->setMetricValue($factTrend, 'setAvgVisitPages', $item, $fieldMap, 'avg_visit_pages');
        $this->setMetricValue($factTrend, 'setTransCount', $item, $fieldMap, 'trans_count');
        $this->setMetricValue($factTrend, 'setTransRatio', $item, $fieldMap, 'trans_ratio');
    }

    /**
     * 设置指标值
     *
     * @param array<mixed> $item
     * @param array<string, int> $fieldMap
     */
    private function setMetricValue(
        FactTrafficTrend $factTrend,
        string $method,
        array $item,
        array $fieldMap,
        string $fieldName,
    ): void {
        if (!isset($fieldMap[$fieldName]) || !is_int($fieldMap[$fieldName]) || !array_key_exists($fieldMap[$fieldName], $item)) {
            return;
        }

        $value = $item[$fieldMap[$fieldName]];

        if ('--' === $value || '' === $value || null === $value) {
            return;
        }

        if (in_array($fieldName, ['bounce_ratio', 'trans_ratio', 'avg_visit_pages'], true)) {
            $stringValue = match (true) {
                is_string($value) => $value,
                is_numeric($value) => (string) $value,
                default => '0',
            };
            $this->callSetterMethod($factTrend, $method, $stringValue);
        } else {
            $this->callSetterMethod($factTrend, $method, is_numeric($value) ? (int) $value : 0);
        }
    }

    private function callSetterMethod(FactTrafficTrend $factTrend, string $method, int|string $value): void
    {
        match ($method) {
            'setPvCount' => $factTrend->setPvCount((int) $value),
            'setVisitCount' => $factTrend->setVisitCount((int) $value),
            'setVisitorCount' => $factTrend->setVisitorCount((int) $value),
            'setIpCount' => $factTrend->setIpCount((int) $value),
            'setBounceRatio' => $factTrend->setBounceRatio((string) $value),
            'setAvgVisitTime' => $factTrend->setAvgVisitTime((int) $value),
            'setAvgVisitPages' => $factTrend->setAvgVisitPages((string) $value),
            'setTransCount' => $factTrend->setTransCount((int) $value),
            'setTransRatio' => $factTrend->setTransRatio((string) $value),
            default => throw TongjiApiException::invalidMethodCall($method),
        };
    }

    /**
     * 从数据项创建FactTrafficTrend实体
     *
     * @param array<mixed> $item
     * @param array<string, int> $fieldMap
     * @param array<string, mixed> $params
     */
    private function createFactTrendFromItem(
        array $item,
        RawTongjiReport $rawReport,
        array $fieldMap,
        array $params,
        string $gran,
    ): ?FactTrafficTrend {
        $dateField = $item[0] ?? null;
        if (!$this->isValidDateField($dateField)) {
            return null;
        }

        // $dateField 在 isValidDateField 中已经验证过是数组且 isset($dateField[0])
        // 但仍需要确认 $dateField[0] 是字符串
        assert(is_array($dateField) && isset($dateField[0]));
        if (!is_string($dateField[0])) {
            $this->logger->warning('日期字段不是字符串', [
                'date_field' => $dateField,
            ]);

            return null;
        }

        try {
            $date = new \DateTimeImmutable($dateField[0]);
        } catch (\Exception $e) {
            $this->logger->warning('日期解析失败', [
                'date_field' => $dateField,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $siteId = $rawReport->getSiteId();
        if ('' === $siteId) {
            throw new \RuntimeException('Site ID cannot be empty for FactTrafficTrend');
        }

        $factTrend = new FactTrafficTrend();
        $factTrend->setSiteId($siteId);
        $factTrend->setDate($date);
        $factTrend->setGran($gran);
        $this->setFactTrendParams($factTrend, $params);
        $this->setFactMetrics($factTrend, $item, $fieldMap);

        return $factTrend;
    }

    /**
     * 验证日期字段是否有效
     *
     * @param mixed $dateField
     */
    private function isValidDateField($dateField): bool
    {
        return null !== $dateField
            && is_array($dateField)
            && isset($dateField[0])
            && is_string($dateField[0]);
    }

    /**
     * 设置FactTrafficTrend的参数
     *
     * @param array<string, mixed> $params
     */
    private function setFactTrendParams(FactTrafficTrend $factTrend, array $params): void
    {
        if (isset($params['source'])) {
            $factTrend->setSourceType(is_string($params['source']) ? $params['source'] : null);
        }
        if (isset($params['clientDevice'])) {
            $factTrend->setDevice(is_string($params['clientDevice']) ? $params['clientDevice'] : null);
        }
        if (isset($params['area'])) {
            $factTrend->setAreaScope(is_string($params['area']) ? $params['area'] : null);
        }
    }
}
