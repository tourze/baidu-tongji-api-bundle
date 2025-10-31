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
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;
use Tourze\BaiduTongjiApiBundle\Entity\TongjiSite;

#[AdminCrud(routePath: '/baidu-tongji/tongji-site', routeName: 'baidu_tongji_tongji_site')]
final class TongjiSiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TongjiSite::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('百度统计站点')
            ->setEntityLabelInPlural('百度统计站点')
            ->setPageTitle('index', '百度统计站点管理')
            ->setPageTitle('new', '新建百度统计站点')
            ->setPageTitle('edit', '编辑百度统计站点')
            ->setPageTitle('detail', '查看百度统计站点')
            ->setSearchFields(['siteId', 'domain'])
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
            ->add(TextFilter::new('domain', '站点域名'))
            ->add(ChoiceFilter::new('status', '站点状态')
                ->setChoices([
                    '正常' => 0,
                    '暂停' => 1,
                ]))
            ->add(DateTimeFilter::new('siteCreateTime', '站点创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('siteId', '站点ID')
            ->setHelp('百度统计提供的站点唯一标识符')
            ->setRequired(true)
        ;

        yield TextField::new('domain', '站点域名')
            ->setHelp('网站的主域名')
            ->setRequired(true)
        ;

        yield ChoiceField::new('status', '站点状态')
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

        yield DateTimeField::new('siteCreateTime', '站点创建时间')
            ->setHelp('百度统计中站点的创建时间')
            ->hideOnIndex()
        ;

        yield AssociationField::new('user', '关联用户')
            ->setFormTypeOption('class', BaiduOAuth2User::class)
            ->setFormTypeOption('choice_label', 'username')
            ->setHelp('绑定的百度OAuth2用户账号')
            ->setRequired(true)
        ;

        yield AssociationField::new('subDirectories', '子目录')
            ->onlyOnDetail()
            ->setTemplatePath('@EasyAdmin/crud/field/association.html.twig')
            ->setHelp('该站点下的所有子目录')
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
