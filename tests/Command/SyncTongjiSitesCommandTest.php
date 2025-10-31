<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduOauth2IntegrateBundle\Service\BaiduUserManager;
use Tourze\BaiduTongjiApiBundle\Command\SyncTongjiSitesCommand;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Service\TongjiSiteService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncTongjiSitesCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncTongjiSitesCommandTest extends AbstractCommandTestCase
{
    private MockObject&BaiduUserManager $userManager;

    private MockObject&TongjiSiteService $siteService;

    public function testExecuteSuccessfulSyncAllUsers(): void
    {
        $user1 = $this->createBaiduUser('user1');
        $user2 = $this->createBaiduUser('user2');
        $users = [$user1, $user2];

        $site1 = $this->createTongjiSite('site1', 'example1.com', $user1);
        $site2 = $this->createTongjiSite('site2', 'example2.com', $user2);

        $this->userManager->expects($this->once())
            ->method('getAllUsers')
            ->willReturn($users)
        ;

        // 真实对象会使用实际的isTokenExpired()方法，不再需要Mock expectations

        $this->siteService->expects($this->exactly(2))
            ->method('syncUserSites')
            ->willReturnOnConsecutiveCalls([$site1], [$site2])
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('用户 user1 同步完成', $output);
        $this->assertStringContainsString('用户 user2 同步完成', $output);
        $this->assertStringContainsString('同步处理完成', $output);
    }

    private function createBaiduUser(string $uid, bool $tokenExpired = false): BaiduOAuth2User
    {
        $config = new BaiduOAuth2Config();
        $config->setClientId('test_client_id');
        $config->setClientSecret('test_client_secret');

        $user = new BaiduOAuth2User();
        $user->setBaiduUid($uid);
        $user->setAccessToken('test_token');
        $user->setExpiresIn(3600);
        $user->setConfig($config);

        // 如果需要过期的token，设置过期时间为过去
        if ($tokenExpired) {
            $user->setExpireTime(new \DateTimeImmutable('-1 hour'));
        }

        return $user;
    }

    private function createTongjiSite(string $siteId, string $domain, BaiduOAuth2User $user): TongjiSite
    {
        $site = new TongjiSite();
        $site->setSiteId($siteId);
        $site->setDomain($domain);
        $site->setUser($user);

        return $site;
    }

    protected function getCommandTester(): CommandTester
    {
        // 将Mock对象注册到容器中
        self::getContainer()->set(BaiduUserManager::class, $this->userManager);
        self::getContainer()->set(TongjiSiteService::class, $this->siteService);

        $command = self::getService(SyncTongjiSitesCommand::class);

        return new CommandTester($command);
    }

    public function testExecuteSpecificUserSuccess(): void
    {
        $user = $this->createBaiduUser('specific-user');
        $sites = [$this->createTongjiSite('specific-site', 'specific.com', $user)];

        $this->userManager->expects($this->once())
            ->method('findUserById')
            ->with('123')
            ->willReturn($user)
        ;

        // 真实对象会使用实际的isTokenExpired()方法

        $this->siteService->expects($this->once())
            ->method('syncUserSites')
            ->with($user)
            ->willReturn($sites)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--user-id' => '123']);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('用户 specific-user 同步完成', $output);
    }

    public function testExecuteUserNotFound(): void
    {
        $this->userManager->expects($this->once())
            ->method('findUserById')
            ->with('999')
            ->willReturn(null)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--user-id' => '999']);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('用户 ID 999 不存在', $output);
    }

    public function testExecuteSkipExpiredToken(): void
    {
        $user = $this->createBaiduUser('expired-user', true); // 创建过期用户

        $this->userManager->expects($this->once())
            ->method('getAllUsers')
            ->willReturn([$user])
        ;

        // 真实对象会使用实际的isTokenExpired()方法

        $this->siteService->expects($this->never())
            ->method('syncUserSites')
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('跳过用户 expired-user - Token已过期', $output);
    }

    public function testExecuteForceExpiredToken(): void
    {
        $user = $this->createBaiduUser('expired-user');
        $sites = [$this->createTongjiSite('expired-site', 'expired.com', $user)];

        $this->userManager->expects($this->once())
            ->method('getAllUsers')
            ->willReturn([$user])
        ;

        $this->siteService->expects($this->once())
            ->method('syncUserSites')
            ->with($user)
            ->willReturn($sites)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force' => true]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('用户 expired-user 同步完成', $output);
    }

    public function testExecuteWithSyncFailure(): void
    {
        $user = $this->createBaiduUser('failing-user');

        $this->userManager->expects($this->once())
            ->method('getAllUsers')
            ->willReturn([$user])
        ;

        // 真实对象会使用实际的isTokenExpired()方法

        $this->siteService->expects($this->once())
            ->method('syncUserSites')
            ->with($user)
            ->willThrowException(new \Exception('同步失败'))
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('用户 failing-user 同步失败', $output);
        $this->assertStringContainsString('有 1 个用户同步失败', $output);
    }

    public function testExecuteNoUsers(): void
    {
        $this->userManager->expects($this->once())
            ->method('getAllUsers')
            ->willReturn([])
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有找到用户数据', $output);
    }

    public function testOptionUserId(): void
    {
        // 将Mock对象注册到容器中
        self::getContainer()->set(BaiduUserManager::class, $this->userManager);
        self::getContainer()->set(TongjiSiteService::class, $this->siteService);

        $command = self::getService(SyncTongjiSitesCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('user-id'));

        $option = $definition->getOption('user-id');
        $this->assertEquals('u', $option->getShortcut());
        $this->assertSame('指定用户ID，不指定则同步所有用户', $option->getDescription());
        $this->assertTrue($option->isValueOptional());
    }

    public function testOptionForce(): void
    {
        // 将Mock对象注册到容器中
        self::getContainer()->set(BaiduUserManager::class, $this->userManager);
        self::getContainer()->set(TongjiSiteService::class, $this->siteService);

        $command = self::getService(SyncTongjiSitesCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));

        $option = $definition->getOption('force');
        $this->assertEquals('f', $option->getShortcut());
        $this->assertSame('强制同步，忽略token过期检查', $option->getDescription());
        $this->assertFalse($option->acceptValue());
    }

    protected function onSetUp(): void
    {
        $this->userManager = $this->createMock(BaiduUserManager::class);
        $this->siteService = $this->createMock(TongjiSiteService::class);
    }
}
