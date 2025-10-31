<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\RouteMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\FactTrafficTrendCrudController;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\RawTongjiReportCrudController;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\TongjiSiteCrudController;
use Tourze\BaiduTongjiApiBundle\Controller\Admin\TongjiSubDirectoryCrudController;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

class AdminMenu implements MenuProviderInterface
{
    /**
     * 获取百度统计API包的管理菜单项.
     *
     * @return array<int, SectionMenuItem|CrudMenuItem>
     */
    public static function getMenuItems(): array
    {
        return [
            MenuItem::section('百度统计管理', 'fas fa-chart-line'),

            MenuItem::linkToCrud('百度统计站点', 'fas fa-globe', TongjiSiteCrudController::getEntityFqcn())
                ->setController(TongjiSiteCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToCrud('子目录管理', 'fas fa-folder', TongjiSubDirectoryCrudController::getEntityFqcn())
                ->setController(TongjiSubDirectoryCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToCrud('原始报告数据', 'fas fa-database', RawTongjiReportCrudController::getEntityFqcn())
                ->setController(RawTongjiReportCrudController::class)
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToCrud('流量趋势分析', 'fas fa-chart-area', FactTrafficTrendCrudController::getEntityFqcn())
                ->setController(FactTrafficTrendCrudController::class)
                ->setPermission('ROLE_ADMIN'),
        ];
    }

    /**
     * 获取子菜单项，用于嵌入到其他菜单分组中.
     *
     * @return array<int, SubMenuItem>
     */
    public static function getSubMenuItems(): array
    {
        return [
            MenuItem::subMenu('百度统计', 'fas fa-chart-line')->setSubItems([
                MenuItem::linkToCrud('站点管理', 'fas fa-globe', TongjiSiteCrudController::getEntityFqcn())
                    ->setController(TongjiSiteCrudController::class),

                MenuItem::linkToCrud('子目录', 'fas fa-folder', TongjiSubDirectoryCrudController::getEntityFqcn())
                    ->setController(TongjiSubDirectoryCrudController::class),

                MenuItem::linkToCrud('原始数据', 'fas fa-database', RawTongjiReportCrudController::getEntityFqcn())
                    ->setController(RawTongjiReportCrudController::class),

                MenuItem::linkToCrud('趋势分析', 'fas fa-chart-area', FactTrafficTrendCrudController::getEntityFqcn())
                    ->setController(FactTrafficTrendCrudController::class),
            ]),
        ];
    }

    /**
     * 获取仪表板统计项.
     *
     * @return array<int, RouteMenuItem>
     */
    public static function getDashboardItems(): array
    {
        return [
            MenuItem::linkToRoute('百度统计概览', 'fas fa-chart-pie', 'admin_baidu_tongji_dashboard')
                ->setPermission('ROLE_USER'),
        ];
    }

    /**
     * 获取快速操作菜单项.
     *
     * @return array<int, CrudMenuItem>
     */
    public static function getQuickActions(): array
    {
        return [
            MenuItem::linkToCrud('新建站点', 'fas fa-plus', TongjiSiteCrudController::getEntityFqcn())
                ->setController(TongjiSiteCrudController::class)
                ->setAction('new')
                ->setPermission('ROLE_ADMIN'),

            MenuItem::linkToCrud('查看趋势', 'fas fa-search', FactTrafficTrendCrudController::getEntityFqcn())
                ->setController(FactTrafficTrendCrudController::class)
                ->setAction('index')
                ->setPermission('ROLE_USER'),
        ];
    }

    /**
     * 实现接口要求的 __invoke 方法.
     */
    public function __invoke(mixed $item): void
    {
        // 接口方法实现
    }
}
