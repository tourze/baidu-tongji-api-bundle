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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\BaiduTongjiApiBundle\Entity\RawTongjiReport;

#[AdminCrud(routePath: '/baidu-tongji/raw-tongji-report', routeName: 'baidu_tongji_raw_tongji_report')]
final class RawTongjiReportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RawTongjiReport::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('百度统计原始报告')
            ->setEntityLabelInPlural('百度统计原始报告')
            ->setPageTitle('index', '百度统计原始报告管理')
            ->setPageTitle('new', '新建百度统计原始报告')
            ->setPageTitle('edit', '编辑百度统计原始报告')
            ->setPageTitle('detail', '查看百度统计原始报告')
            ->setSearchFields(['siteId', 'method', 'responseHash'])
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
            ->add(TextFilter::new('method', '报告方法'))
            ->add(DateTimeFilter::new('fetchedAt', '拉取时间'))
            ->add(TextFilter::new('syncStatus', '同步状态'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('siteId', '站点ID')
            ->setHelp('百度统计站点标识符')
            ->setRequired(true)
        ;

        yield TextField::new('method', '报告方法')
            ->setHelp('统计报告的方法类型，如 trend/time、source/all 等')
            ->setRequired(true)
        ;

        yield DateField::new('startDate', '开始日期')
            ->setHelp('报告数据的开始日期')
            ->setRequired(true)
        ;

        yield DateField::new('endDate', '结束日期')
            ->setHelp('报告数据的结束日期')
            ->setRequired(true)
        ;

        yield TextField::new('responseHash', '响应哈希')
            ->setHelp('用于去重的响应体哈希值')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('fetchedAt', '拉取时间')
            ->setHelp('从百度统计API获取数据的时间')
            ->setFormTypeOption('disabled', true)
        ;

        yield DateTimeField::new('processedAt', '处理时间')
            ->setHelp('数据处理完成的时间')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('syncStatus', '同步状态')
            ->setChoices([
                '未同步' => null,
                '同步中' => 0,
                '同步成功' => 1,
                '同步失败' => 2,
            ])
            ->renderAsBadges([
                null => 'secondary',
                0 => 'warning',
                1 => 'success',
                2 => 'danger',
            ])
            ->hideOnIndex()
        ;

        yield TextareaField::new('metrics', '指标列表')
            ->setHelp('报告包含的指标名称，逗号分隔')
            ->hideOnIndex()
        ;

        yield CodeEditorField::new('paramsJson', '附加参数')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('请求时使用的附加参数JSON')
        ;

        yield CodeEditorField::new('dataJson', '原始响应数据')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('百度统计API返回的原始JSON数据')
        ;

        yield TextareaField::new('errorMessage', '错误信息')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('数据处理或同步过程中的错误信息')
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
