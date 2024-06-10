<?php

namespace App\Controller\Admin;

use App\Entity\Node;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use App\Admin\Field\VichImageField;
use App\Admin\Field\VichFileField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Region;
use App\Service\Data;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
// use Doctrine\Common\Collections\ArrayCollection;
// use Doctrine\Common\Collections\Criteria;

class NodeCrudController extends AbstractCrudController
{
    private $region;
    private $query;
    private $adminUrlGenerator;

    public function __construct(Data $data, RequestStack $requestStack, AdminUrlGenerator $adminUrlGenerator)
    {
        $request = $requestStack->getCurrentRequest();
        $regionId = $request->query->get('region');
        if (!is_null($regionId)) {
            $this->region = $data->getRegion($regionId);
        }
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->data = $data;
        $this->request = $request;
    }
    
    public static function getEntityFqcn(): string
    {
        return Node::class;
    }
    
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if (!is_null($this->region)) {
            $regionId = $this->region->getId();
            $qb
                ->andWhere("r.id = $regionId")
                ->leftJoin('entity.regions', 'r')
            ;
        }
        return $qb;
    }
    
    public function createEntity(string $entityFqcn)
    {
        $node = new Node();
        if (!is_null($this->region)) {
            $lastNode = $this->data->findNodesByRegion($this->region, $this->request->getLocale(), 1);
            if (!empty($lastNode)) {
                foreach ($lastNode[0]->getRegions() as $r) {
                    $node->addRegion($r);
                }
            } else {
                $node->addRegion($this->region);
            }
        }
        return $node;
    }

    public function configureActions(Actions $actions): Actions
    {
        $newFn = fn (Action $action) => $action->linkToUrl(
            fn () => $this->adminUrlGenerator
                          ->setAction('new')
                          ->generateUrl()
        );
        $editFn = fn (Action $action) => $action->linkToUrl(
            fn (Node $entity) => $this->adminUrlGenerator
                          ->setAction('edit')
                          ->set('entityId', $entity->getId())
                          ->generateUrl()
        );
        
        return $actions
            ->update('index', 'new', $newFn)
            ->update('index', 'edit', $editFn)
            ->update('detail', 'edit', $editFn)
        ;
    }
    
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile(
                Asset::new('/js/ckeditor.js')
                    ->onlyOnForms()
            )
            ->addJsFile(
                Asset::new('/js/initCKEditor.js')
                    ->defer()
                    ->onlyOnForms()
            )
        ;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        if (!is_null($this->region)) {
            return $crud
                ->setPageTitle('index', $this->region)
            ;
        } else {
            return $crud;
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $idField = IdField::new('id')->onlyOnIndex();
        $titleField = TextField::new('title');
        $imageField = ImageField::new('image')
            ->onlyOnIndex()
            ->setBasePath('images/')
            ->setUploadDir('public/images/')
        ;

        $vichImageField = VichImageField::new('imageFile', 'Image')->hideOnIndex();
        $videoField = VichFileField::new('videoFile', 'Video')->hideOnIndex();
        $tagsFieldOnIndex = ArrayField::new('tags')->onlyOnIndex();
        $tagsField = AssociationField::new('tags')
            ->onlyOnForms()
            // ->setRequired(true)
        ;
        $categoryFieldOnIndex = ArrayField::new('category')->onlyOnIndex();
        $categoryField = AssociationField::new('category')->onlyOnForms();
        $summaryField = TextareaField::new('summary')
            // ->setMaxLength(15)
            ;
        $bodyField = TextareaField::new('body')->setNumOfRows(10)->onlyOnForms();
        $createdAtField = DateTimeField::new('createdAt')->onlyOnIndex();
        $updatedAtField = DateTimeField::new('updatedAt')->onlyOnIndex();
        $languageField = AssociationField::new('language');
        $regionsField = AssociationField::new('regions')->onlyOnForms();
        $specsField = CollectionField::new('specs')->useEntryCrudForm()->hideOnIndex();
        $imagesField = CollectionField::new('images')->useEntryCrudForm()->hideOnIndex();
        
        $fields = [];
        if (!is_null($this->region)) {
            $fields = $this->region->getFields();
            $vichImageField->setHelp("推荐尺寸{$this->region->getDescription()}，或宽高比与之相同的尺寸。");
        } else if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $fields = Data::GetProperties(new Node());
            array_push($fields, 'regions');
        }
        
        yield $titleField;
        foreach ($fields as $f) {
            $ff = $f . "Field";
            yield $$ff;
        }
        // yield ArrayField::new('regions')->onlyOnIndex();
        if (in_array('image', $fields)) {
            yield $vichImageField;
        }
        if (in_array('tags', $fields)) {
            yield $tagsFieldOnIndex;
        }
        if (in_array('category', $fields)) {
            yield $categoryFieldOnIndex;
        }
    }
}
