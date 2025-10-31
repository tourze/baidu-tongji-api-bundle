<?php

declare(strict_types=1);

namespace Tourze\BaiduTongjiApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSubDirectory;

#[AdminCrud(routePath: '/baidu-tongji/tongji-sub-directory', routeName: 'baidu_tongji_tongji_sub_directory')]
final class TongjiSubDirectoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TongjiSubDirectory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('百度统计子目录')
            ->setEntityLabelInPlural('百度统计子目录')
            ->setPageTitle('index', '百度统计子目录管理')
            ->setPageTitle('new', '新建百度统计子目录')
            ->setPageTitle('edit', '编辑百度统计子目录')
            ->setPageTitle('detail', '查看百度统计子目录')
            ->setSearchFields(['subDirId', 'subDir'])
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
            ->add(TextFilter::new('subDirId', '子目录ID'))
            ->add(TextFilter::new('subDir', '子目录路径'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('subDirId', '子目录ID')
            ->setHelp('百度统计提供的子目录唯一标识符')
            ->setRequired(true)
        ;

        yield TextField::new('subDir', '子目录路径')
            ->setHelp('网站子目录的路径')
            ->setRequired(true)
        ;

        yield ChoiceField::new('status', '子目录状态')
            ->setChoices([
                '正常' => 0,
                '暂停' => 1,
            ])
            ->renderExpanded(false)
            ->renderAsBadges([
                0 => 'success',
                1 => 'warning',
            ])
        ;

        yield DateTimeField::new('subDirCreateTime', '子目录创建时间')
            ->setHelp('百度统计中子目录的创建时间')
            ->hideOnIndex()
        ;

        yield AssociationField::new('site', '所属站点')
            ->setFormTypeOption('class', TongjiSite::class)
            ->setFormTypeOption('choice_label', function (TongjiSite $site): string {
                return sprintf('[%s] %s', $site->getSiteId(), $site->getDomain());
            })
            ->setHelp('该子目录所属的百度统计站点')
            ->setRequired(true)
        ;

        yield CodeEditorField::new('rawData', '原始数据')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('来自百度统计API的原始JSON数据')
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
