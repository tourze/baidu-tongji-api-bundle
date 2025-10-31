<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\BaiduTongjiApiBundle\Entity\FactTrafficTrend;

#[AdminCrud(routePath: '/baidu-tongji/fact-traffic-trend', routeName: 'baidu_tongji_fact_traffic_trend')]
final class FactTrafficTrendCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FactTrafficTrend::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('流量趋势分析')
            ->setEntityLabelInPlural('流量趋势分析')
            ->setPageTitle('index', '流量趋势分析管理')
            ->setPageTitle('new', '新建流量趋势分析')
            ->setPageTitle('edit', '编辑流量趋势分析')
            ->setPageTitle('detail', '查看流量趋势分析')
            ->setSearchFields(['siteId'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('siteId', '站点ID'))
            ->add(TextFilter::new('gran', '时间粒度'))
            ->add(TextFilter::new('sourceType', '流量类型'))
            ->add(TextFilter::new('device', '设备类型'))
            ->add(DateTimeFilter::new('date', '日期'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('siteId', '站点ID')
            ->setHelp('百度统计站点标识符')
            ->setRequired(true)
        ;

        yield DateField::new('date', '统计日期')
            ->setHelp('数据统计的日期')
            ->setRequired(true)
        ;

        yield ChoiceField::new('gran', '时间粒度')
            ->setChoices([
                '日' => 'day',
                '周' => 'week',
                '月' => 'month',
            ])
            ->setHelp('统计数据的时间颗粒度')
            ->setRequired(true)
        ;

        yield TextField::new('sourceType', '流量类型')
            ->setHelp('访问流量来源类型')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('device', '设备类型')
            ->setChoices([
                'PC端' => 'pc',
                '移动端' => 'mobile',
            ])
            ->renderAsBadges([
                'pc' => 'primary',
                'mobile' => 'info',
            ])
            ->hideOnIndex()
        ;

        yield TextField::new('areaScope', '区域范围')
            ->setHelp('访问用户的地域范围')
            ->hideOnIndex()
        ;

        // 流量指标分组
        yield IntegerField::new('pvCount', '页面浏览量(PV)')
            ->setHelp('页面访问总数')
            ->setFormTypeOption('attr', ['min' => 0])
        ;

        yield IntegerField::new('visitCount', '访问次数')
            ->setHelp('用户访问网站的总次数')
            ->setFormTypeOption('attr', ['min' => 0])
        ;

        yield IntegerField::new('visitorCount', '访客数(UV)')
            ->setHelp('独立访客数量')
            ->setFormTypeOption('attr', ['min' => 0])
            ->hideOnIndex()
        ;

        yield IntegerField::new('ipCount', 'IP数')
            ->setHelp('独立IP访问数')
            ->setFormTypeOption('attr', ['min' => 0])
            ->hideOnIndex()
        ;

        // 行为指标分组
        yield NumberField::new('bounceRatio', '跳出率(%)')
            ->setNumDecimals(2)
            ->setHelp('用户只访问一个页面就离开的比例')
            ->hideOnIndex()
        ;

        yield IntegerField::new('avgVisitTime', '平均访问时长(秒)')
            ->setHelp('用户平均停留时间，单位：秒')
            ->setFormTypeOption('attr', ['min' => 0])
            ->hideOnIndex()
        ;

        yield NumberField::new('avgVisitPages', '平均访问页数')
            ->setNumDecimals(2)
            ->setHelp('每次访问平均浏览的页面数')
            ->hideOnIndex()
        ;

        // 转化指标分组
        yield IntegerField::new('transCount', '转化次数')
            ->setHelp('完成转化目标的次数')
            ->setFormTypeOption('attr', ['min' => 0])
            ->hideOnIndex()
        ;

        yield NumberField::new('transRatio', '转化率(%)')
            ->setNumDecimals(2)
            ->setHelp('访问转化为目标的比例')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '记录创建时间')
            ->onlyOnDetail()
            ->setFormTypeOption('disabled', true)
        ;

        yield DateTimeField::new('updateTime', '记录更新时间')
            ->onlyOnDetail()
            ->setFormTypeOption('disabled', true)
        ;
    }
}
