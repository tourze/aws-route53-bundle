<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AwsRoute53Bundle\Entity\AwsAccount;

/**
 * @extends AbstractCrudController<AwsAccount>
 */
#[AdminCrud(routePath: '/aws-route53/aws-account', routeName: 'aws_route53_aws_account')]
final class AwsAccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AwsAccount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AWS账户')
            ->setEntityLabelInPlural('AWS账户')
            ->setPageTitle('index', 'AWS账户管理')
            ->setPageTitle('detail', 'AWS账户详情')
            ->setPageTitle('new', '创建AWS账户')
            ->setPageTitle('edit', '编辑AWS账户')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setHelp('index', '管理AWS账户信息，包括凭证配置和区域设置')
            ->setHelp('new', '创建新的AWS账户配置，请确保提供正确的凭证信息')
            ->setHelp('edit', '修改AWS账户配置，谨慎更改凭证参数以避免影响现有服务')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->setHelp('系统生成的UUID主键')
        ;

        yield TextField::new('name', '账户名称')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('AWS账户的显示名称，便于识别和管理')
        ;

        yield TextField::new('accountId', 'AWS账户ID')
            ->setMaxLength(12)
            ->setHelp('12位数字的AWS账户ID，可选填写')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('partition', 'AWS分区')
            ->setChoices([
                'AWS标准' => 'aws',
                '中国区域' => 'aws-cn',
                '美国政府云' => 'aws-us-gov',
            ])
            ->setRequired(true)
            ->setHelp('选择AWS服务分区类型')
        ;

        yield TextField::new('defaultRegion', '默认区域')
            ->setRequired(true)
            ->setMaxLength(50)
            ->setHelp('AWS服务的默认区域，如us-east-1')
        ;

        yield UrlField::new('endpoint', '自定义终端节点')
            ->hideOnIndex()
            ->setHelp('可选的自定义AWS服务终端节点URL')
        ;

        yield ChoiceField::new('credentialsType', '凭证类型')
            ->setChoices([
                'Profile配置' => 'profile',
                'Access Key' => 'access_key',
                '角色承担' => 'assume_role',
                'Web Identity' => 'web_identity',
                '实例配置文件' => 'instance_profile',
            ])
            ->setRequired(true)
            ->setHelp('选择AWS凭证的配置方式')
        ;

        yield ArrayField::new('credentialsParams', '凭证参数')
            ->hideOnIndex()
            ->setHelp('凭证配置的JSON参数，根据凭证类型提供相应配置')
        ;

        yield ArrayField::new('tags', 'AWS标签')
            ->hideOnIndex()
            ->setHelp('AWS资源标签信息，JSON格式')
        ;

        yield BooleanField::new('enabled', '启用状态')
            ->setHelp('是否启用此AWS账户配置')
        ;

        yield AssociationField::new('hostedZones', '托管区域')
            ->hideOnForm()
            ->setHelp('该账户下的Route53托管区域列表')
        ;

        yield DateTimeField::new('createdAt', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('记录创建的时间')
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('记录最后更新的时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '账户名称'))
            ->add(TextFilter::new('accountId', 'AWS账户ID'))
            ->add(
                ChoiceFilter::new('partition', 'AWS分区')
                    ->setChoices([
                        'AWS标准' => 'aws',
                        '中国区域' => 'aws-cn',
                        '美国政府云' => 'aws-us-gov',
                    ])
            )
            ->add(
                ChoiceFilter::new('credentialsType', '凭证类型')
                    ->setChoices([
                        'Profile配置' => 'profile',
                        'Access Key' => 'access_key',
                        '角色承担' => 'assume_role',
                        'Web Identity' => 'web_identity',
                        '实例配置文件' => 'instance_profile',
                    ])
            )
            ->add(BooleanFilter::new('enabled', '启用状态'))
        ;
    }
}
