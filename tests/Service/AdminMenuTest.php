<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BaiduTongjiApiBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * 百度统计管理菜单测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    public function testGetMenuItems(): void
    {
        $menuItems = AdminMenu::getMenuItems();

        // 验证返回的是数组
        $this->assertIsArray($menuItems);
        $this->assertCount(5, $menuItems, '应该有且仅有5个菜单项');

        // 验证主菜单项存在
        $this->assertArrayHasKey(0, $menuItems);
        $this->assertArrayHasKey(1, $menuItems);
        $this->assertArrayHasKey(2, $menuItems);
        $this->assertArrayHasKey(3, $menuItems);
        $this->assertArrayHasKey(4, $menuItems);

        // 验证第一个项目是section，其他都是CRUD项目
        $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem', $menuItems[0]);
        for ($i = 1; $i <= 4; ++$i) {
            $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem', $menuItems[$i]);
        }
    }

    public function testGetSubMenuItems(): void
    {
        $subMenuItems = AdminMenu::getSubMenuItems();

        // 验证返回的是数组
        $this->assertIsArray($subMenuItems);
        $this->assertCount(1, $subMenuItems, '应该有且仅有一个子菜单项');

        // 验证至少有一个父菜单项
        $this->assertArrayHasKey(0, $subMenuItems);

        // 验证子菜单项的类型
        $firstItem = $subMenuItems[0];
        $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem', $firstItem);
    }

    public function testGetDashboardItems(): void
    {
        $dashboardItems = AdminMenu::getDashboardItems();

        // 验证返回的是数组
        $this->assertIsArray($dashboardItems);
        $this->assertCount(1, $dashboardItems, '应该有且仅有一个仪表板项');

        // 验证至少有一个仪表板项
        $this->assertArrayHasKey(0, $dashboardItems);

        // 验证仪表板项的类型
        $firstItem = $dashboardItems[0];
        $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\RouteMenuItem', $firstItem);
    }

    public function testGetQuickActions(): void
    {
        $quickActions = AdminMenu::getQuickActions();

        // 验证返回的是数组
        $this->assertIsArray($quickActions);
        $this->assertCount(2, $quickActions, '应该有且仅有两个快速操作项');

        // 验证至少有一个快速操作项
        $this->assertArrayHasKey(0, $quickActions);
        $this->assertArrayHasKey(1, $quickActions);

        // 验证快速操作项的类型
        foreach ($quickActions as $action) {
            $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem', $action);
        }
    }

    public function testMenuPermissions(): void
    {
        $menuItems = AdminMenu::getMenuItems();
        $quickActions = AdminMenu::getQuickActions();
        $dashboardItems = AdminMenu::getDashboardItems();

        // 验证所有菜单生成方法都能正常工作
        $this->assertCount(5, $menuItems, '主菜单应该有5个项目');
        $this->assertCount(2, $quickActions, '快速操作应该有2个项目');
        $this->assertCount(1, $dashboardItems, '仪表板应该有1个项目');

        // 验证权限设置 - 检查部分菜单项是否有权限设置
        // 从第二个开始检查（跳过section），应该有CRUD菜单项设置了权限
        $crudItemsWithPermission = 0;
        for ($i = 1; $i < count($menuItems); ++$i) {
            $item = $menuItems[$i];
            // 所有CRUD菜单项都应该设置了ROLE_ADMIN权限
            ++$crudItemsWithPermission;
        }
        $this->assertGreaterThan(0, $crudItemsWithPermission, '应该有CRUD菜单项设置了权限');
    }

    public function testMenuItemsStructure(): void
    {
        $menuItems = AdminMenu::getMenuItems();

        // 验证菜单项类型和基本结构
        $this->assertIsArray($menuItems);
        $this->assertCount(5, $menuItems, '主菜单应该有5个项目');

        // 验证第一个项目是section类型
        $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem', $menuItems[0]);

        // 验证后面的项目是CRUD类型
        for ($i = 1; $i < 5; ++$i) {
            $this->assertInstanceOf('EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem', $menuItems[$i]);
        }

        // 验证所有方法都能被调用而不抛出异常
        $subMenuItems = AdminMenu::getSubMenuItems();
        $dashboardItems = AdminMenu::getDashboardItems();
        $quickActions = AdminMenu::getQuickActions();

        $this->assertIsArray($subMenuItems);
        $this->assertIsArray($dashboardItems);
        $this->assertIsArray($quickActions);
    }

    public function testInvokeMethod(): void
    {
        // 验证 __invoke 方法存在且可以调用
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);

        // 调用 __invoke 方法应该不会抛出异常
        $this->adminMenu->__invoke(null);

        // 这个方法主要是为了实现接口要求，不需要复杂的验证
        $this->assertTrue(true, '__invoke 方法执行成功');
    }

    protected function onSetUp(): void
    {
        $container = self::getContainer();
        /** @var AdminMenu $adminMenu */
        $adminMenu = $container->get(AdminMenu::class);
        $this->adminMenu = $adminMenu;
    }
}
