<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ReviewController extends AbstractController
{
    #[Route('/produs/{slug}/recenzie', name: 'app_review_create', methods: ['POST'])]
    public function create(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Product $product,
        Request $request,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($reviewRepository->findOneByProductAndUser($product, $user)) {
            $this->addFlash('error', 'Ai lăsat deja o recenzie pentru acest produs.');

            return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
        }

        $review = (new Review())
            ->setProduct($product)
            ->setUser($user)
        ;

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();
            $this->addFlash('success', 'Recenzia ta a fost trimisă și va apărea după moderare.');
        } else {
            $this->addFlash('error', 'Recenzia nu a putut fi trimisă. Verifică rating-ul și comentariul.');
        }

        return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
    }
}
