<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/cont', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    /**
     * Ștergere cont (drept GDPR la ștergere). Comenzile deja plasate NU se
     * șterg — sunt necesare pentru evidența contabilă/legală — dar contul
     * e anonimizat (nume, email, telefon) și tot ce e strict personal și
     * nu are nevoie de păstrare legală (adrese, coș) se șterge efectiv.
     */
    #[Route('/cont/sterge', name: 'app_account_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        AddressRepository $addressRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
        StoreConfig $store,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('account_delete', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        foreach ($addressRepository->findByUser($user) as $address) {
            $entityManager->remove($address);
        }

        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if ($cart) {
            $entityManager->remove($cart);
        }

        $user
            ->setEmail(sprintf('cont-sters-%d@%s', $userId, $store->domain ?: 'sters.local'))
            ->setFirstName('Cont')
            ->setLastName('șters')
            ->setPhone(null)
            ->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(32))))
            ->setRoles([])
        ;

        $entityManager->flush();

        $request->getSession()->invalidate();
        $tokenStorage->setToken(null);

        $this->addFlash('success', 'Contul tău a fost șters.');

        return $this->redirectToRoute('app_home');
    }
}
