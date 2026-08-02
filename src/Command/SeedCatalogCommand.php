<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\StockStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-catalog', description: 'Populează categorii și produse de test pentru catalog')]
final class SeedCatalogCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = [
            'Pixuri și Creioane' => [
                ['Pilot G2 Retractable, 0.7mm', 'Kyoto', StockStatus::InStock, 42, null, '18.90'],
                ['Uni-ball Jetstream Premier', 'Osaka', StockStatus::InStock, 25, null, '32.50'],
                ['Tombow Mono Zero Radieră Pen', 'Tokyo', StockStatus::OnOrder, null, 12, '24.00'],
                ['Pentel Kerry Creion Mecanic 0.5mm', 'Tokyo', StockStatus::InStock, 15, null, '45.00'],
            ],
            'Caiete și Agende' => [
                ['Midori MD Notebook A5, Liniat', 'Tokyo', StockStatus::InStock, 30, null, '38.00'],
                ['Kokuyo Campus Caiet, Set 5 buc', 'Osaka', StockStatus::InStock, 60, null, '22.00'],
                ['Hobonichi Techo Agendă Zilnică', 'Tokyo', StockStatus::OnOrder, null, 21, '135.00'],
                ['Traveler\'s Notebook, Piele Naturală', 'Kyoto', StockStatus::OnOrder, null, 18, '210.00'],
            ],
            'Washi Tape și Decor' => [
                ['MT Washi Tape, Set 5 Modele Sakura', 'Kyoto', StockStatus::InStock, 50, null, '28.00'],
                ['Kamiiso Washi Tape Aurie', 'Kyoto', StockStatus::InStock, 20, null, '19.50'],
                ['Midori Stickere Decorative, Set', 'Tokyo', StockStatus::OnOrder, null, 14, '15.00'],
            ],
            'Instrumente Tradiționale' => [
                ['Perie Caligrafie Shodo, Bambus', 'Nara', StockStatus::OnOrder, null, 25, '55.00'],
                ['Cerneală Sumi Tradițională, 60ml', 'Nara', StockStatus::OnOrder, null, 25, '48.00'],
                ['Suzuri, Piatră pentru Cerneală', 'Kyoto', StockStatus::InStock, 8, null, '95.00'],
            ],
        ];

        $count = 0;
        foreach ($categories as $categoryName => $products) {
            $category = new Category();
            $category->setName($categoryName);
            $this->entityManager->persist($category);

            foreach ($products as [$name, $origin, $stockStatus, $stock, $estimatedDays, $price]) {
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
                ++$count;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d categorii și %d produse create.', count($categories), $count));

        return Command::SUCCESS;
    }
}
