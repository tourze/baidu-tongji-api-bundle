<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\BaiduTongjiApiBundle\DependencyInjection\BaiduTongjiApiExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(BaiduTongjiApiExtension::class)]
final class BaiduTongjiApiExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private BaiduTongjiApiExtension $extension;

    private ContainerBuilder $container;

    public function testLoadLoadsServicesYaml(): void
    {
        $this->extension->load([], $this->container);

        // Check that services are loaded by verifying some key services exist
        $definitions = $this->container->getDefinitions();
        $bundleServiceIds = array_filter(array_keys($definitions), function (string $id) {
            return str_starts_with($id, 'Tourze\BaiduTongjiApiBundle\\');
        });

        $this->assertGreaterThan(0, count($bundleServiceIds), 'Bundle services should be loaded');

        // Check for specific services that should be defined
        $expectedServices = [
            'Tourze\BaiduTongjiApiBundle\Repository\FactTrafficTrendRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\TongjiSubDirectoryRepository',
            'Tourze\BaiduTongjiApiBundle\Service\TongjiApiClient',
            'Tourze\BaiduTongjiApiBundle\Service\TongjiReportSyncService',
            'Tourze\BaiduTongjiApiBundle\Service\TongjiSiteService',
            'Tourze\BaiduTongjiApiBundle\Command\SyncTongjiReportCommand',
            'Tourze\BaiduTongjiApiBundle\Command\SyncTongjiSitesCommand',
        ];

        foreach ($expectedServices as $serviceId) {
            $this->assertTrue(
                $this->container->hasDefinition($serviceId),
                "Service {$serviceId} should be defined"
            );
        }
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $this->extension->load([], $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadWithNonEmptyConfigs(): void
    {
        $configs = [
            ['some_config' => 'value'],
        ];

        $this->extension->load($configs, $this->container);

        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }

    public function testLoadSetsCorrectAutowiring(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autowiring is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'Tourze\BaiduTongjiApiBundle\\')) {
                $this->assertTrue($definition->isAutowired(), "Service {$id} should be autowired");
            }
        }
    }

    public function testLoadSetsCorrectAutoconfiguration(): void
    {
        $this->extension->load([], $this->container);

        $definitions = $this->container->getDefinitions();

        // Check that autoconfiguration is enabled for bundle services
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'Tourze\BaiduTongjiApiBundle\\')) {
                $this->assertTrue($definition->isAutoconfigured(), "Service {$id} should be autoconfigured");
            }
        }
    }

    public function testLoadMultipleCalls(): void
    {
        $this->extension->load([], $this->container);
        $firstCount = count($this->container->getDefinitions());

        // Loading again should not duplicate services
        $this->extension->load([], $this->container);
        $secondCount = count($this->container->getDefinitions());

        $this->assertEquals($firstCount, $secondCount);
    }

    public function testExtensionInheritsFromCorrectClass(): void
    {
        $this->assertInstanceOf(
            AutoExtension::class,
            $this->extension
        );
    }

    public function testLoadDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();

        $this->extension->load([], $this->container);
        $this->extension->load([['key' => 'value']], $this->container);
        $this->extension->load([[], ['another' => 'config']], $this->container);
    }

    public function testGetConfigDirReturnsCorrectPath(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);

        $configDir = $method->invoke($this->extension);

        $this->assertIsString($configDir);
        $this->assertStringContainsString('/Resources/config', $configDir);
        $this->assertDirectoryExists($configDir);
    }

    public function testLoadRepositoryServices(): void
    {
        $this->extension->load([], $this->container);

        $repositories = [
            'Tourze\BaiduTongjiApiBundle\Repository\FactTrafficTrendRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\RawTongjiReportRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\TongjiSiteRepository',
            'Tourze\BaiduTongjiApiBundle\Repository\TongjiSubDirectoryRepository',
        ];

        foreach ($repositories as $repositoryId) {
            $definition = $this->container->getDefinition($repositoryId);
            $this->assertTrue($definition->hasTag('doctrine.repository_service'));
        }
    }

    public function testLoadServiceAliases(): void
    {
        $this->extension->load([], $this->container);

        // Check for specific service aliases defined in services.yaml
        $expectedAliases = [
            'tourze.baidu_tongji_api.client',
            'tourze.baidu_tongji_api.site_service',
        ];

        foreach ($expectedAliases as $alias) {
            $this->assertTrue(
                $this->container->hasDefinition($alias),
                "Service alias {$alias} should be defined"
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new BaiduTongjiApiExtension();
        $this->container = new ContainerBuilder();

        // Set required parameters for AutoExtension
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.logs_dir', sys_get_temp_dir());
        $this->container->setParameter('kernel.project_dir', __DIR__ . '/../../');
    }
}
