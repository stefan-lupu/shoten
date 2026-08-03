<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\StockStatus;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-catalog', description: 'Populează categorii (ierarhice) și produse de test — idempotent, sigur de rulat de mai multe ori')]
final class SeedCatalogCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Structură: nume categorie => [părinte|null, [produse]]. Ordinea contează —
        // părintele trebuie definit înaintea copilului. Exemplu de ierarhie pe 3
        // niveluri: Papetărie > Scris > Pixuri și Creioane.
        $tree = [
            'Papetărie' => [null, []],
            'Scris' => ['Papetărie', []],
            'Pixuri și Creioane' => ['Scris', [
                ['Pilot G2 Retractable, 0.7mm', 'Kyoto', StockStatus::InStock, 42, null, '18.90'],
                ['Uni-ball Jetstream Premier', 'Osaka', StockStatus::InStock, 25, null, '32.50'],
                ['Tombow Mono Zero Radieră Pen', 'Tokyo', StockStatus::OnOrder, null, 12, '24.00'],
                ['Pentel Kerry Creion Mecanic 0.5mm', 'Tokyo', StockStatus::InStock, 15, null, '45.00'],
            ]],
            'Caiete și Agende' => ['Scris', [
                ['Midori MD Notebook A5, Liniat', 'Tokyo', StockStatus::InStock, 30, null, '38.00'],
                ['Kokuyo Campus Caiet, Set 5 buc', 'Osaka', StockStatus::InStock, 60, null, '22.00'],
                ['Hobonichi Techo Agendă Zilnică', 'Tokyo', StockStatus::OnOrder, null, 21, '135.00'],
                ['Traveler\'s Notebook, Piele Naturală', 'Kyoto', StockStatus::OnOrder, null, 18, '210.00'],
            ]],
            'Decor & Accesorii' => [null, []],
            'Washi Tape și Decor' => ['Decor & Accesorii', [
                ['MT Washi Tape, Set 5 Modele Sakura', 'Kyoto', StockStatus::InStock, 50, null, '28.00'],
                ['Kamiiso Washi Tape Aurie', 'Kyoto', StockStatus::InStock, 20, null, '19.50'],
                ['Midori Stickere Decorative, Set', 'Tokyo', StockStatus::OnOrder, null, 14, '15.00'],
            ]],
            'Instrumente Tradiționale' => ['Decor & Accesorii', [
                ['Perie Caligrafie Shodo, Bambus', 'Nara', StockStatus::OnOrder, null, 25, '55.00'],
                ['Cerneală Sumi Tradițională, 60ml', 'Nara', StockStatus::OnOrder, null, 25, '48.00'],
                ['Suzuri, Piatră pentru Cerneală', 'Kyoto', StockStatus::InStock, 8, null, '95.00'],
            ]],
        ];

        /** @var array<string, Category> $createdCategories */
        $createdCategories = [];
        /** @var array<string, int> $siblingCounters cheie = numele părintelui ('' = rădăcină) */
        $siblingCounters = [];
        $categoryCount = 0;
        $productCount = 0;

        foreach ($tree as $categoryName => [$parentName, $products]) {
            $category = $this->categoryRepository->findOneBy(['name' => $categoryName]);
            if (!$category) {
                $category = new Category();
                $category->setName($categoryName);
                $this->entityManager->persist($category);
                ++$categoryCount;
            }

            if ($parentName) {
                $category->setParent($createdCategories[$parentName] ?? $this->categoryRepository->findOneBy(['name' => $parentName]));
            }

            $siblingKey = $parentName ?? '';
            $category->setOrderNo($siblingCounters[$siblingKey] ??= 0);
            $siblingCounters[$siblingKey] = $siblingCounters[$siblingKey] + 1;

            $createdCategories[$categoryName] = $category;

            foreach ($products as [$name, $origin, $stockStatus, $stock, $estimatedDays, $price]) {
                if ($this->productRepository->findOneBy(['name' => $name])) {
                    continue;
                }

                $product = new Product();
                $product->setName($name);
                $product->setDescription(sprintf('%s, adus direct din %s.', $name, $origin));
                $product->setPrice($price);
                $product->setOrigin($origin);
                $product->setStockStatus($stockStatus);
                $product->setStock($stock ?? 0);
                $product->setEstimatedDays($estimatedDays);
                $product->setCategory($category);
                $this->entityManager->persist($product);
                ++$productCount;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d categorii noi și %d produse noi create (restul existau deja).', $categoryCount, $productCount));

        return Command::SUCCESS;
    }
}
