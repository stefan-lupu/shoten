<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\User;
use App\Enum\WholesaleStatus;
use App\Form\WholesaleApplicationType;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
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
     * Cerere de cont angro — vezi tasks/15-conturi-angro.md. Aprobarea e
     * mereu manuală din admin; aici clientul doar trimite datele firmei
     * și vede statusul cererii lui curente.
     */
    #[Route('/cont/angro', name: 'app_account_wholesale')]
    public function wholesale(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, StoreConfig $store): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (\in_array($user->getWholesaleStatus(), [WholesaleStatus::Pending, WholesaleStatus::Approved], true)) {
            return $this->render('account/wholesale.html.twig', ['user' => $user]);
        }

        $form = $this->createForm(WholesaleApplicationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setWholesaleStatus(WholesaleStatus::Pending);
            $user->setWholesaleRequestedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new EmailAddress($store->email, $store->name))
                ->to($user->getEmail())
                ->subject('Cererea ta de cont angro a fost primită')
                ->htmlTemplate('emails/wholesale_request_received.html.twig')
                ->context(['user' => $user])
            ;
            $mailer->send($email);

            $this->addFlash('success', 'Cererea ta de cont angro a fost trimisă. Te anunțăm pe email când e procesată.');

            return $this->redirectToRoute('app_account_wholesale');
        }

        return $this->render('account/wholesale.html.twig', [
            'user' => $user,
            'wholesaleForm' => $form,
        ]);
    }

    /**
     * Export date personale (drept GDPR la portabilitatea datelor) — un
     * JSON descărcabil cu tot ce ținem despre client: profil, adrese,
     * istoricul comenzilor (inclusiv adresa de livrare salvată la momentul
     * fiecărei comenzi, care poate diferi de adresele curente).
     */
    #[Route('/cont/date/descarca', name: 'app_account_export', methods: ['GET'])]
    public function exportData(AddressRepository $addressRepository, OrderRepository $orderRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = [
            'cont' => [
                'email' => $user->getEmail(),
                'prenume' => $user->getFirstName(),
                'nume' => $user->getLastName(),
                'telefon' => $user->getPhone(),
                'cont_creat_la' => $user->getCreatedAt()?->format('c'),
            ],
            'adrese' => array_map(static fn ($address) => [
                'nume_complet' => $address->getFullName(),
                'telefon' => $address->getPhone(),
                'judet' => $address->getCounty(),
                'localitate' => $address->getCity(),
                'strada' => $address->getStreet(),
                'cod_postal' => $address->getPostalCode(),
                'principala' => $address->isDefault(),
            ], $addressRepository->findByUser($user)),
            'comenzi' => array_map(static fn ($order) => [
                'id' => $order->getId(),
                'data' => $order->getCreatedAt()?->format('c'),
                'status' => $order->getStatus()->value,
                'metoda_plata' => $order->getPaymentMethod()?->value,
                'status_plata' => $order->getPaymentStatus()->value,
                'total' => $order->getTotal(),
                'cost_transport' => $order->getShippingCost(),
                'adresa_livrare' => [
                    'nume_complet' => $order->getShippingFullName(),
                    'telefon' => $order->getShippingPhone(),
                    'judet' => $order->getShippingCounty(),
                    'localitate' => $order->getShippingCity(),
                    'strada' => $order->getShippingStreet(),
                    'cod_postal' => $order->getShippingPostalCode(),
                ],
                'produse' => array_map(static fn ($item) => [
                    'produs' => $item->getProductName(),
                    'cantitate' => $item->getQuantity(),
                    'pret_unitar' => $item->getUnitPrice(),
                ], $order->getItems()->toArray()),
            ], $orderRepository->findByUser($user)),
        ];

        $response = new JsonResponse($data, 200, [], false);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response->headers->set('Content-Disposition', 'attachment; filename="datele-mele.json"');

        return $response;
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
