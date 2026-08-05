<?php

namespace App\Controller\Admin;

use App\Dto\NewsletterBroadcast;
use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterBroadcastType;
use App\Repository\NewsletterSubscriberRepository;
use App\Service\StoreConfig;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class NewsletterSubscriberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NewsletterSubscriber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonat newsletter')
            ->setEntityLabelInPlural('Abonați newsletter')
            ->setDefaultSort(['subscribedAt' => 'DESC'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');
        yield BooleanField::new('consentGiven', 'Consimțământ')->renderAsSwitch(false);
        yield DateTimeField::new('subscribedAt', 'Abonat la');
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportCsv = Action::new('exportCsv', 'Exportă CSV', 'fa fa-file-csv')
            ->linkToRoute('admin_newsletter_export_csv')
            ->createAsGlobalAction()
        ;

        $compose = Action::new('compose', 'Compune newsletter', 'fa fa-paper-plane')
            ->linkToRoute('admin_newsletter_subscriber_compose')
            ->createAsGlobalAction()
        ;

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $exportCsv)
            ->add(Crud::PAGE_INDEX, $compose)
        ;
    }

    #[Route('/admin/newsletter/export.csv', name: 'admin_newsletter_export_csv')]
    #[IsGranted('ROLE_ADMIN')]
    public function exportCsv(NewsletterSubscriberRepository $repository): StreamedResponse
    {
        $subscribers = $repository->findAll();

        $response = new StreamedResponse(function () use ($subscribers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'data_abonare']);
            foreach ($subscribers as $subscriber) {
                fputcsv($handle, [
                    $subscriber->getEmail(),
                    $subscriber->getSubscribedAt()?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="newsletter-abonati.csv"');

        return $response;
    }

    #[AdminRoute(path: '/newsletter/compune', name: 'compose')]
    #[IsGranted('ROLE_ADMIN')]
    public function compose(
        Request $request,
        NewsletterSubscriberRepository $repository,
        MailerInterface $mailer,
        StoreConfig $store,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $broadcast = new NewsletterBroadcast();
        $form = $this->createForm(NewsletterBroadcastType::class, $broadcast);
        $form->handleRequest($request);

        $subscribers = $repository->findBy(['consentGiven' => true]);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($subscribers as $subscriber) {
                $email = (new TemplatedEmail())
                    ->from(new Address($store->email, $store->name))
                    ->to($subscriber->getEmail())
                    ->subject($broadcast->subject)
                    ->htmlTemplate('emails/newsletter_broadcast.html.twig')
                    ->context([
                        'body' => $broadcast->body,
                        'unsubscribeUrl' => $urlGenerator->generate('app_newsletter_unsubscribe', ['token' => $subscriber->getUnsubscribeToken()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ])
                ;
                $mailer->send($email);
            }

            $this->addFlash('success', sprintf('Newsletter trimis către %d abonați.', count($subscribers)));

            return $this->redirectToRoute('admin_newsletter_subscriber_compose');
        }

        return $this->render('admin/newsletter_compose.html.twig', [
            'broadcastForm' => $form,
            'subscriberCount' => count($subscribers),
        ]);
    }
}
