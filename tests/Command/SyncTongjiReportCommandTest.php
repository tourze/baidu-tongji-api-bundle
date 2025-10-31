<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\BaiduTongjiApiBundle\Command\SyncTongjiReportCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncTongjiReportCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncTongjiReportCommandTest extends AbstractCommandTestCase
{
    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SyncTongjiReportCommand::class);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
        // No setup required for these tests
    }

    public function testCommandDefinition(): void
    {
        $command = self::getService(SyncTongjiReportCommand::class);
        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('tongji:sync-report', $command->getName());
    }

    public function testExecuteWithInvalidMethod(): void
    {
        $commandTester = $this->getCommandTester();

        $result = $commandTester->execute([
            'method' => 'invalid-method',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
        ]);

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString('不支持的报告方法', $commandTester->getDisplay());
    }

    public function testExecuteWithInvalidDateRange(): void
    {
        $commandTester = $this->getCommandTester();

        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-02',
            '--end-date' => '2024-01-01',
        ]);

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString('开始日期不能晚于结束日期', $commandTester->getDisplay());
    }

    public function testExecuteWithInvalidDateFormat(): void
    {
        $commandTester = $this->getCommandTester();

        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => 'invalid-date',
            '--end-date' => '2024-01-01',
        ]);

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString('日期格式错误', $commandTester->getDisplay());
    }

    public function testArgumentMethod(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with valid method
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
        ]);

        // Should not fail due to method validation (other failures expected due to missing data)
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('不支持的报告方法', $output);
        $this->assertStringNotContainsString('不支持的方法', $output);
    }

    public function testOptionSiteId(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with site-id option
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--site-id' => 'test-site',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
        ]);

        // Should process site-id option without validation errors
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('不支持的报告方法', $output);
    }

    public function testOptionStartDate(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with start-date option
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
        ]);

        // Should process start-date option without validation errors
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('必须指定开始日期', $output);
    }

    public function testOptionEndDate(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with end-date option
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
        ]);

        // Should process end-date option without validation errors
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('必须指定结束日期', $output);
    }

    public function testOptionParams(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with params option
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
            '--params' => '{"gran":"day","metrics":"pv_count"}',
        ]);

        // Should process params option without validation errors
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('参数格式错误', $output);
    }

    public function testOptionForce(): void
    {
        $commandTester = $this->getCommandTester();

        // Test with force option
        $result = $commandTester->execute([
            'method' => 'trend/time/a',
            '--start-date' => '2024-01-01',
            '--end-date' => '2024-01-02',
            '--force' => true,
        ]);

        // Should process force option without validation errors
        $output = $commandTester->getDisplay();
        $this->assertStringNotContainsString('强制选项错误', $output);
    }
}
