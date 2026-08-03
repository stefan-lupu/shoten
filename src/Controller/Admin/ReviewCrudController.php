<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Enum\ReviewStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Recenzie')
            ->setEntityLabelInPlural('Recenzii')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produs');
        yield AssociationField::new('user', 'Client');
        yield IntegerField::new('rating', 'Rating');
        yield TextareaField::new('comment', 'Comentariu');
        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'În așteptare' => ReviewStatus::Pending,
                'Aprobată' => ReviewStatus::Approved,
                'Respinsă' => ReviewStatus::Rejected,
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ])
        ;
        yield DateTimeField::new('createdAt')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'În așteptare' => ReviewStatus::Pending,
                'Aprobată' => ReviewStatus::Approved,
                'Respinsă' => ReviewStatus::Rejected,
            ]))
        ;
    }

    /**
     * Fără niciun filtru aplicat explicit, lista arată doar recenziile
     * pending — moderarea zilnică nu trebuie să caute printre cele deja
     * procesate.
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!isset($searchDto->getAppliedFilters()['status'])) {
            $qb->andWhere('entity.status = :defaultStatus')
                ->setParameter('defaultStatus', ReviewStatus::Pending)
            ;
        }

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'Aprobă', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->displayIf(static fn (Review $review) => ReviewStatus::Approved !== $review->getStatus())
            ->setCssClass('text-success')
        ;
        $reject = Action::new('reject', 'Respinge', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->displayIf(static fn (Review $review) => ReviewStatus::Rejected !== $review->getStatus())
            ->setCssClass('text-danger')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject)
        ;
    }

    #[AdminRoute(path: '/approve', name: 'approve')]
    public function approve(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        return $this->updateStatus($request, $entityManager, $adminUrlGenerator, ReviewStatus::Approved);
    }

    #[AdminRoute(path: '/reject', name: 'reject')]
    public function reject(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        return $this->updateStatus($request, $entityManager, $adminUrlGenerator, ReviewStatus::Rejected);
    }

    private function updateStatus(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator, ReviewStatus $status): RedirectResponse
    {
        $review = $entityManager->getRepository(Review::class)->find($request->query->get('entityId'));
        if ($review) {
            $review->setStatus($status);
            $entityManager->flush();
        }

        $url = $adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl();

        return $this->redirect($url);
    }
}
