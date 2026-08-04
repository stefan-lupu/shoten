<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cont/adrese')]
#[IsGranted('ROLE_USER')]
final class AddressController extends AbstractController
{
    #[Route('', name: 'app_address_index', methods: ['GET'])]
    public function index(AddressRepository $addressRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('address/index.html.twig', [
            'addresses' => $addressRepository->findByUser($user),
        ]);
    }

    #[Route('/noua', name: 'app_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = (new Address())->setUser($user);
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Prima adresă a userului devine automat principală.
            if (empty($addressRepository->findByUser($user))) {
                $address->setIsDefault(true);
            }

            $entityManager->persist($address);
            $entityManager->flush();
            $this->addFlash('success', 'Adresa a fost adăugată.');

            if ('checkout' === $request->query->get('redirect_to')) {
                return $this->redirectToRoute('app_checkout');
            }

            return $this->redirectToRoute('app_address_index');
        }

        return $this->render('address/form.html.twig', [
            'form' => $form,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/editeaza', name: 'app_address_edit', methods: ['GET', 'POST'])]
    public function edit(Address $address, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertOwnsAddress($address);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Adresa a fost actualizată.');

            return $this->redirectToRoute('app_address_index');
        }

        return $this->render('address/form.html.twig', [
            'form' => $form,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/sterge', name: 'app_address_delete', methods: ['POST'])]
    public function delete(Address $address, Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->assertOwnsAddress($address);
        $this->assertCsrfToken($request);

        /** @var User $user */
        $user = $this->getUser();
        $wasDefault = $address->isDefault();

        $entityManager->remove($address);
        $entityManager->flush();

        if ($wasDefault) {
            $remaining = $addressRepository->findByUser($user);
            if ($remaining) {
                $remaining[0]->setIsDefault(true);
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Adresa a fost ștearsă.');

        return $this->redirectToRoute('app_address_index');
    }

    #[Route('/{id}/principala', name: 'app_address_set_default', methods: ['POST'])]
    public function setDefault(Address $address, Request $request, AddressRepository $addressRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->assertOwnsAddress($address);
        $this->assertCsrfToken($request);

        /** @var User $user */
        $user = $this->getUser();
        foreach ($addressRepository->findByUser($user) as $userAddress) {
            $userAddress->setIsDefault($userAddress === $address);
        }
        $entityManager->flush();

        $this->addFlash('success', 'Adresa a fost setată ca principală.');

        return $this->redirectToRoute('app_address_index');
    }

    private function assertOwnsAddress(Address $address): void
    {
        if ($address->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Această adresă nu îți aparține.');
        }
    }

    private function assertCsrfToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid('address_action', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }
    }
}
