<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduUserManager;
use Tourze\BaiduTongjiApiBundle\Service\TongjiSiteService;

#[AsCommand(
    name: self::NAME,
    description: '同步所有用户的百度统计站点数据'
)]
class SyncTongjiSitesCommand extends Command
{
    public const NAME = 'tongji:sync-sites';

    public function __construct(
        private BaiduUserManager $userManager,
        private TongjiSiteService $siteService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '指定用户ID，不指定则同步所有用户')
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制同步，忽略token过期检查')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user-id');
        $force = (bool) $input->getOption('force');

        $io->title('百度统计站点同步');

        $users = $this->getUsersToSync($userId, $io);
        if (null === $users) {
            return Command::FAILURE;
        }

        if ([] === $users) {
            $io->warning('没有找到用户数据');

            return Command::SUCCESS;
        }

        $result = $this->syncAllUsers($users, $force, $io);
        $this->displayResults($result, $io);

        return $result['errorCount'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * 获取要同步的用户列表
     * @return BaiduOAuth2User[]|null
     */
    private function getUsersToSync(mixed $userId, SymfonyStyle $io): ?array
    {
        if (null !== $userId && is_scalar($userId)) {
            $userIdString = is_string($userId) || is_numeric($userId) ? (string) $userId : '';
            if ('' === $userIdString) {
                $io->error('用户 ID 不能为空');

                return null;
            }
            $user = $this->userManager->findUserById($userIdString);
            if (null === $user) {
                $io->error("用户 ID {$userIdString} 不存在");

                return null;
            }

            return [$user];
        }

        return $this->userManager->getAllUsers();
    }

    /**
     * 同步所有用户的站点数据
     * @param BaiduOAuth2User[] $users
     * @return array{totalUsers: int, successCount: int, errorCount: int, skippedCount: int}
     */
    private function syncAllUsers(array $users, bool $force, SymfonyStyle $io): array
    {
        $totalUsers = count($users);
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        $io->progressStart($totalUsers);

        foreach ($users as $user) {
            $result = $this->syncSingleUser($user, $force, $io);
            if ('success' === $result) {
                ++$successCount;
            } elseif ('error' === $result) {
                ++$errorCount;
            } else {
                ++$skippedCount;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        return [
            'totalUsers' => $totalUsers,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'skippedCount' => $skippedCount,
        ];
    }

    /**
     * 同步单个用户的站点数据
     * @return 'success'|'skipped'|'error'
     */
    private function syncSingleUser(BaiduOAuth2User $user, bool $force, SymfonyStyle $io): string
    {
        try {
            if (!$force && $user->isTokenExpired()) {
                $io->writeln(sprintf("\n<comment>跳过用户 %s - Token已过期</comment>", $user->getBaiduUid()));

                return 'skipped';
            }

            $sites = $this->siteService->syncUserSites($user);
            $siteCount = count($sites);

            $io->writeln(sprintf("\n<info>用户 %s 同步完成 - %d个站点</info>", $user->getBaiduUid(), $siteCount));

            return 'success';
        } catch (\Exception $e) {
            $io->writeln(sprintf("\n<error>用户 %s 同步失败: %s</error>", $user->getBaiduUid(), $e->getMessage()));

            return 'error';
        }
    }

    /**
     * 显示同步结果
     * @param array{totalUsers: int, successCount: int, errorCount: int, skippedCount: int} $result
     */
    private function displayResults(array $result, SymfonyStyle $io): void
    {
        $io->section('同步结果统计');
        $io->table(
            ['统计项', '数量'],
            [
                ['总用户数', $result['totalUsers']],
                ['成功同步', $result['successCount']],
                ['跳过用户', $result['skippedCount']],
                ['同步失败', $result['errorCount']],
            ]
        );

        if ($result['errorCount'] > 0) {
            $io->warning("有 {$result['errorCount']} 个用户同步失败，请检查日志");
        } else {
            $io->success('同步处理完成！');
        }
    }
}
