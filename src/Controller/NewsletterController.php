<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterSubscriptionType;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterController extends AbstractController
{
    /**
     * Fragment independent, randat via render(controller(...)) în footer
     * (base.html.twig) — apare identic pe orice pagină, fără ca fiecare
     * controller să trebuiască să construiască/paseze formularul.
     */
    public function widget(): Response
    {
        $form = $this->createForm(NewsletterSubscriptionType::class, new NewsletterSubscriber());

        return $this->render('newsletter/_widget.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/newsletter/abonare', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterSubscriptionType::class, $subscriber);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subscriber);
            $entityManager->flush();
            $this->addFlash('success', 'Te-ai abonat cu succes la newsletter!');
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', $errors ? implode(' ', $errors) : 'Abonarea nu a putut fi finalizată.');
        }

        return $this->redirectToRefererOrHome($request);
    }

    #[Route('/newsletter/dezabonare/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(string $token, NewsletterSubscriberRepository $repository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $subscriber = $repository->findOneByUnsubscribeToken($token);
        if ($subscriber) {
            $entityManager->remove($subscriber);
            $entityManager->flush();
            $this->addFlash('success', 'Te-ai dezabonat cu succes. Ne pare rău să te vedem plecând!');
        } else {
            $this->addFlash('error', 'Link de dezabonare invalid sau deja folosit.');
        }

        return $this->redirectToRoute('app_home');
    }

    private function redirectToRefererOrHome(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');
        if ($referer && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }
}
