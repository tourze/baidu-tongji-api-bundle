<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository;
use Tourze\BaiduTongjiApiBundle\Service\TongjiReportSyncService;

#[AsCommand(
    name: self::NAME,
    description: '同步百度统计报告数据'
)]
class SyncTongjiReportCommand extends Command
{
    public const NAME = 'tongji:sync-report';
    private const SUPPORTED_METHODS = [
        'trend/time/a' => '趋势分析',
        'trend/latest/a' => '实时访客',
        'pro/product/a' => '推广方式',
        'pro/hour/a' => '百度推广趋势',
        'source/all/a' => '全部来源',
        'source/engine/a' => '搜索引擎',
        'source/searchword/a' => '搜索词',
        'source/link/a' => '外部链接',
        'custom/media/a' => '指定广告跟踪',
        'visit/toppage/a' => '受访页面',
        'visit/landingpage/a' => '入口页面',
        'visit/topdomain/a' => '受访域名',
        'visit/district/a' => '地域分布(中国)',
        'visit/world/a' => '世界地域分布',
        'overview/getTimeTrendRpt' => '网站概况(趋势数据)',
        'overview/getDistrictRpt' => '网站概况(地域分布)',
        'overview/getCommonTrackRpt' => '网站概况(常用轨迹)',
    ];

    public function __construct(
        private TongjiSiteRepository $siteRepository,
        private TongjiReportSyncService $syncService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('method', InputArgument::REQUIRED, '报告方法: ' . implode(', ', array_keys(self::SUPPORTED_METHODS)))
            ->addOption('site-id', 's', InputOption::VALUE_OPTIONAL, '指定站点ID，不指定则同步所有站点')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '开始日期(YYYY-MM-DD)')
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '结束日期(YYYY-MM-DD)')
            ->addOption('params', 'p', InputOption::VALUE_OPTIONAL, '附加参数(JSON格式)', '{}')
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制同步，忽略token过期检查')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 验证输入参数
        $validationResult = $this->validateInput($input, $io);
        if (Command::SUCCESS !== $validationResult) {
            return $validationResult;
        }

        // 解析参数
        $parameters = $this->parseInputParameters($input);
        $io->title(sprintf('同步%s报告', self::SUPPORTED_METHODS[$parameters['method']]));

        // 执行同步并返回结果
        return $this->performSync($parameters, $io);
    }

    /**
     * 解析输入参数
     * @return array{method: string, siteId: string|null, startDate: \DateTimeImmutable, endDate: \DateTimeImmutable, params: array<string, mixed>, force: bool}
     */
    private function parseInputParameters(InputInterface $input): array
    {
        return [
            'method' => $this->parseStringArgument($input->getArgument('method')),
            'siteId' => $this->parseOptionalStringOption($input->getOption('site-id')),
            'startDate' => $this->parseDateOption($input->getOption('start-date')),
            'endDate' => $this->parseDateOption($input->getOption('end-date')),
            'params' => $this->parseParamsOption($input->getOption('params')),
            'force' => (bool) $input->getOption('force'),
        ];
    }

    private function parseStringArgument(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }

    private function parseOptionalStringOption(mixed $value): ?string
    {
        return null !== $value && (is_string($value) || is_numeric($value)) ? (string) $value : null;
    }

    private function parseDateOption(mixed $value): \DateTimeImmutable
    {
        $dateValue = null !== $value && (is_string($value) || is_numeric($value)) ? (string) $value : '';

        return new \DateTimeImmutable($dateValue);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseParamsOption(mixed $value): array
    {
        $paramsValue = null !== $value && (is_string($value) || is_numeric($value)) ? (string) $value : '{}';
        $decodedParams = json_decode($paramsValue, true, 512, JSON_THROW_ON_ERROR);
        $params = [];

        if (is_array($decodedParams)) {
            foreach ($decodedParams as $key => $paramValue) {
                if (is_string($key)) {
                    $params[$key] = $paramValue;
                }
            }
        }

        return $params;
    }

    /**
     * 执行同步过程
     * @param array{method: string, siteId: string|null, startDate: \DateTimeImmutable, endDate: \DateTimeImmutable, params: array<string, mixed>, force: bool} $parameters
     */
    private function performSync(array $parameters, SymfonyStyle $io): int
    {
        // 获取站点列表
        $sites = $this->getSitesToSync($parameters['siteId'], $io);
        if ([] === $sites) {
            $io->warning('没有找到站点数据');

            return Command::SUCCESS;
        }

        // 执行同步
        $result = $this->executeSyncForSites(
            $sites,
            $parameters['method'],
            $parameters['startDate'],
            $parameters['endDate'],
            $parameters['params'],
            $parameters['force'],
            $io
        );

        // 显示统计结果
        $this->displaySyncResults($result, $io);

        return $result['errorCount'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * 验证输入参数
     */
    private function validateInput(InputInterface $input, SymfonyStyle $io): int
    {
        $methodValue = $input->getArgument('method');
        $method = is_string($methodValue) || is_numeric($methodValue) ? (string) $methodValue : '';
        if (!array_key_exists($method, self::SUPPORTED_METHODS)) {
            $io->error("不支持的报告方法: {$method}");
            $io->note('支持的方法: ' . implode(', ', array_keys(self::SUPPORTED_METHODS)));

            return Command::FAILURE;
        }

        $startDateStr = $input->getOption('start-date');
        $endDateStr = $input->getOption('end-date');
        if (null === $startDateStr || null === $endDateStr) {
            $io->error('必须指定开始日期和结束日期');

            return Command::FAILURE;
        }

        try {
            $startDateValue = is_string($startDateStr) || is_numeric($startDateStr) ? (string) $startDateStr : '';
            $endDateValue = is_string($endDateStr) || is_numeric($endDateStr) ? (string) $endDateStr : '';
            $startDate = new \DateTimeImmutable($startDateValue);
            $endDate = new \DateTimeImmutable($endDateValue);

            // 检查日期范围是否合理
            if ($startDate > $endDate) {
                $io->error('开始日期不能晚于结束日期');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error("日期格式错误: {$e->getMessage()}");

            return Command::FAILURE;
        }

        try {
            $paramsOption = $input->getOption('params');
            $paramsValue = null !== $paramsOption && (is_string($paramsOption) || is_numeric($paramsOption)) ? (string) $paramsOption : '{}';
            json_decode($paramsValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error("参数JSON格式错误: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 获取要同步的站点列表
     *
     * @return array<TongjiSite>
     */
    private function getSitesToSync(?string $siteId, SymfonyStyle $io): array
    {
        if (null !== $siteId) {
            $site = $this->siteRepository->findOneBy(['siteId' => $siteId]);
            if (null === $site) {
                $io->error("站点 ID {$siteId} 不存在");

                return [];
            }

            return [$site];
        }

        return $this->siteRepository->findAll();
    }

    /**
     * 为所有站点执行同步
     *
     * @param array<mixed> $sites
     * @param array<string, mixed> $params
     * @return array{totalSites: int, successCount: int, errorCount: int}
     */
    private function executeSyncForSites(
        array $sites,
        string $method,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $params,
        bool $force,
        SymfonyStyle $io,
    ): array {
        $totalSites = count($sites);
        $successCount = 0;
        $errorCount = 0;

        $io->section(sprintf('开始同步 %d 个站点的数据', $totalSites));
        $io->progressStart($totalSites);

        foreach ($sites as $site) {
            if (!$site instanceof TongjiSite) {
                $this->logger->warning('Invalid site object encountered', ['site' => $site]);
                ++$errorCount;
                $io->progressAdvance();
                continue;
            }

            $result = $this->syncSingleSite($site, $method, $startDate, $endDate, $params, $force, $io);
            if ($result) {
                ++$successCount;
            } else {
                ++$errorCount;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        return [
            'totalSites' => $totalSites,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
        ];
    }

    /**
     * 同步单个站点
     *
     * @param TongjiSite $site
     * @param array<string, mixed> $params
     */
    private function syncSingleSite(
        TongjiSite $site,
        string $method,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $params,
        bool $force,
        SymfonyStyle $io,
    ): bool {
        $user = $site->getUser();
        if (null === $user) {
            $io->writeln(sprintf("\n<error>站点 %s 无关联用户</error>", $site->getSiteId()));

            return false;
        }

        try {
            // 检查 token 是否过期
            if (!$force && $user->isTokenExpired()) {
                $io->writeln(sprintf("\n<comment>跳过站点 %s - 用户Token已过期</comment>", $site->getSiteId()));

                return false;
            }

            $this->executeReportSync($method, $user, $site->getSiteId(), $startDate, $endDate, $params, $io);
            $io->writeln(sprintf("\n<info>站点 %s 同步完成</info>", $site->getSiteId()));

            return true;
        } catch (\Exception $e) {
            $io->writeln(sprintf("\n<error>站点 %s 同步失败: %s</error>", $site->getSiteId(), $e->getMessage()));

            return false;
        }
    }

    /**
     * 执行具体的报告同步
     *
     * @param BaiduOAuth2User $user
     * @param array<string, mixed> $params
     */
    private function executeReportSync(
        string $method,
        BaiduOAuth2User $user,
        string $siteId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $params,
        SymfonyStyle $io,
    ): void {
        switch ($method) {
            case 'trend/time/a':
                $this->syncService->syncTrendTimeReport($user, $siteId, $startDate, $endDate, $params);
                break;
            default:
                $io->writeln(sprintf("\n<comment>站点 %s - 方法 %s 尚未实现</comment>", $siteId, $method));
        }
    }

    /**
     * 显示同步结果统计
     *
     * @param array{totalSites: int, successCount: int, errorCount: int} $result
     */
    private function displaySyncResults(array $result, SymfonyStyle $io): void
    {
        $io->section('同步结果统计');
        $io->table(
            ['统计项', '数量'],
            [
                ['总站点数', $result['totalSites']],
                ['成功同步', $result['successCount']],
                ['同步失败', $result['errorCount']],
            ]
        );

        if ($result['errorCount'] > 0) {
            $io->warning("有 {$result['errorCount']} 个站点同步失败，请检查日志");
        } else {
            $io->success('所有站点报告同步完成！');
        }
    }
}
