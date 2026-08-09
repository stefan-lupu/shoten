<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductWholesaleTierRepository;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Punct central de rezolvare a prețului unitar — vezi tasks/16-preturi-angro.md.
 * Orice loc din aplicație care are nevoie de prețul „real” plătit de un
 * client pentru un produs (nu doar `Product::getPrice()`, care e prețul de
 * retail brut) trebuie să treacă prin acest serviciu.
 */
final readonly class WholesalePricingResolver
{
    public function __construct(
        private ProductWholesaleTierRepository $tierRepository,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function resolveUnitPrice(Product $product, int $quantity, ?User $user): string
    {
        // $user->getRoles() întoarce doar rolurile brute stocate pe entitate —
        // NU expandează role_hierarchy (ex: ROLE_ADMIN → ROLE_WHOLESALE din
        // security.yaml). Trecem prin RoleHierarchyInterface ca un admin să
        // vadă exact prețurile pe care le-ar vedea un cont angro aprobat,
        // la fel cum face deja `is_granted()` în Twig.
        if (!$user || !\in_array('ROLE_WHOLESALE', $this->roleHierarchy->getReachableRoleNames($user->getRoles()), true)) {
            return $product->getPrice();
        }

        $tier = $this->tierRepository->findApplicableTier($product, $quantity);

        return $tier?->getUnitPrice() ?? $product->getPrice();
    }
}
