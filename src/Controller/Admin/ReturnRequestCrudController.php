<?php

namespace App\Controller\Admin;

use App\Entity\ReturnRequest;
use App\Enum\ReturnStatus;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReturnRequestCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ReturnRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Retur')
            ->setEntityLabelInPlural('Retururi')
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('order', 'Comandă')->hideOnForm();
        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'În analiză' => ReturnStatus::Requested,
                'Aprobat' => ReturnStatus::Approved,
                'Respins' => ReturnStatus::Rejected,
                'Finalizat' => ReturnStatus::Completed,
            ])
            ->renderAsBadges([
                'requested' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                'completed' => 'secondary',
            ])
            ->hideOnForm()
        ;
        yield TextareaField::new('reason', 'Motiv (client)')->hideOnForm();
        yield TextareaField::new('adminNote', 'Notă admin')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('createdAt', 'Cerut la')->hideOnForm();
        yield DateTimeField::new('processedAt', 'Procesat la')->hideOnIndex()->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Status')->setChoices([
                'În analiză' => ReturnStatus::Requested,
                'Aprobat' => ReturnStatus::Approved,
                'Respins' => ReturnStatus::Rejected,
                'Finalizat' => ReturnStatus::Completed,
            ]))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $process = Action::new('process', 'Procesează', 'fa fa-gavel')
            ->linkToUrl(fn (ReturnRequest $r) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('process')
                ->setEntityId($r->getId())
                ->generateUrl())
            ->displayIf(static fn (ReturnRequest $r) => ReturnStatus::Requested === $r->getStatus())
        ;
        $markCompleted = Action::new('markCompleted', 'Marchează finalizat', 'fa fa-check-double')
            ->linkToUrl(fn (ReturnRequest $r) => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('markCompleted')
                ->setEntityId($r->getId())
                ->set('_token', $this->csrfTokenManager->getToken('return_complete_'.$r->getId())->getValue())
                ->generateUrl())
            ->displayIf(static fn (ReturnRequest $r) => ReturnStatus::Approved === $r->getStatus())
        ;

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $process)
            ->add(Crud::PAGE_INDEX, $markCompleted)
            ->add(Crud::PAGE_DETAIL, $process)
            ->add(Crud::PAGE_DETAIL, $markCompleted)
        ;
    }

    #[AdminRoute(path: '/process', name: 'process')]
    #[IsGranted('ROLE_ADMIN')]
    public function process(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        StoreConfig $store,
    ): Response {
        $returnRequest = $entityManager->getRepository(ReturnRequest::class)->find($request->query->get('entityId'));
        if (!$returnRequest) {
            throw $this->createNotFoundException('Cerere de retur inexistentă.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('return_process_'.$returnRequest->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token de securitate invalid — reîncearcă.');

                return $this->redirectToReturn($returnRequest->getId());
            }

            if (ReturnStatus::Requested === $returnRequest->getStatus()) {
                $decision = $request->request->get('decision');
                $note = trim((string) $request->request->get('note'));
                $approved = 'approve' === $decision;

                $returnRequest
                    ->setStatus($approved ? ReturnStatus::Approved : ReturnStatus::Rejected)
                    ->setAdminNote($note ?: null)
                    ->markProcessed()
                ;
                $entityManager->flush();

                $order = $returnRequest->getOrder();
                $mailer->send((new TemplatedEmail())
                    ->from(new EmailAddress($store->email, $store->name))
                    ->to($order->getUser()->getEmail())
                    ->subject(sprintf('Cererea de retur pentru comanda #%d a fost %s', $order->getId(), $approved ? 'aprobată' : 'respinsă'))
                    ->htmlTemplate($approved ? 'emails/return_approved.html.twig' : 'emails/return_rejected.html.twig')
                    ->context(['order' => $order, 'returnRequest' => $returnRequest])
                );

                $this->addFlash('success', $approved ? 'Retur aprobat.' : 'Retur respins.');
            }

            return $this->redirectToReturn($returnRequest->getId());
        }

        return $this->render('admin/return_process.html.twig', ['returnRequest' => $returnRequest]);
    }

    #[AdminRoute(path: '/mark-completed', name: 'mark_completed')]
    #[IsGranted('ROLE_ADMIN')]
    public function markCompleted(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $returnRequest = $entityManager->getRepository(ReturnRequest::class)->find($request->query->get('entityId'));
        if ($returnRequest && !$this->isCsrfTokenValid('return_complete_'.$returnRequest->getId(), $request->query->get('_token'))) {
            $this->addFlash('danger', 'Token de securitate invalid — reîncearcă.');

            return $this->redirectToReturn($returnRequest->getId());
        }
        if ($returnRequest && ReturnStatus::Approved === $returnRequest->getStatus()) {
            $returnRequest->setStatus(ReturnStatus::Completed);
            $entityManager->flush();
            $this->addFlash('success', 'Retur marcat ca finalizat.');
        }

        return $this->redirectToReturn($returnRequest?->getId());
    }

    private function redirectToReturn(?int $id): RedirectResponse
    {
        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Action::DETAIL)->setEntityId($id)->generateUrl());
    }
}
