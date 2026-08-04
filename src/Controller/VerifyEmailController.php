<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[IsGranted('ROLE_USER')]
final class VerifyEmailController extends AbstractController
{
    #[Route('/verifica-email/{id}', name: 'app_verify_email')]
    public function verify(
        int $id,
        Request $request,
        UserRepository $userRepository,
        VerifyEmailHelperInterface $verifyEmailHelper,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $id) {
            $this->addFlash('error', 'Acest link de confirmare nu este pentru contul tău curent.');

            return $this->redirectToRoute('app_account');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        try {
            $verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Linkul de confirmare este invalid sau a expirat: '.$e->getReason());

            return $this->redirectToRoute('app_account');
        }

        $user->setVerified(true);
        $entityManager->flush();

        $this->addFlash('success', 'Adresa de email a fost confirmată. Mulțumim!');

        return $this->redirectToRoute('app_account');
    }

    #[Route('/verifica-email/retrimite', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resend(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, MailerInterface $mailer, StoreConfig $store): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('verify_email_resend', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            $signature = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                (string) $user->getId(),
                (string) $user->getEmail(),
                ['id' => $user->getId()],
            );

            $email = (new TemplatedEmail())
                ->from(new EmailAddress($store->email, $store->name))
                ->to((string) $user->getEmail())
                ->subject(sprintf('Confirmă adresa de email — %s', $store->name))
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context(['signedUrl' => $signature->getSignedUrl()])
            ;
            $mailer->send($email);
        }

        $this->addFlash('success', 'Am retrimis emailul de confirmare.');

        return $this->redirectToRoute('app_account');
    }
}
