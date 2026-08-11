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
                ['Zebra Sarasa Clip 0.5mm, Set 5 Culori', 'Tokyo', StockStatus::InStock, 38, null, '29.00'],
                ['Pilot Frixion Ball, Pix Radiabil', 'Kyoto', StockStatus::InStock, 55, null, '16.50'],
                ['Uni Kuru Toga, Creion cu Mină Rotativă', 'Osaka', StockStatus::InStock, 27, null, '39.00'],
            ]],
            'Caiete și Agende' => ['Scris', [
                ['Midori MD Notebook A5, Liniat', 'Tokyo', StockStatus::InStock, 30, null, '38.00'],
                ['Kokuyo Campus Caiet, Set 5 buc', 'Osaka', StockStatus::InStock, 60, null, '22.00'],
                ['Hobonichi Techo Agendă Zilnică', 'Tokyo', StockStatus::OnOrder, null, 21, '135.00'],
                ['Traveler\'s Notebook, Piele Naturală', 'Kyoto', StockStatus::OnOrder, null, 18, '210.00'],
                ['Kokuyo Sketchbook Dot Grid B5', 'Osaka', StockStatus::InStock, 33, null, '34.00'],
                ['Maruman Mnemosyne Notepad A5', 'Nagoya', StockStatus::InStock, 20, null, '41.00'],
            ]],
            'Stilouri și Cerneală' => ['Scris', [
                ['Sailor Pro Gear Slim, Stilou', 'Hiroshima', StockStatus::InStock, 9, null, '320.00'],
                ['Pilot Kakuno, Stilou pentru Începători', 'Tokyo', StockStatus::InStock, 40, null, '58.00'],
                ['Pilot Iroshizuku, Cerneală 50ml', 'Tokyo', StockStatus::InStock, 18, null, '95.00'],
                ['Platinum Preppy, Stilou 0.3mm', 'Nagoya', StockStatus::InStock, 52, null, '24.00'],
            ]],
            'Radiere și Corectoare' => ['Scris', [
                ['Tombow Mono, Radieră Set 3 buc', 'Tokyo', StockStatus::InStock, 80, null, '12.00'],
                ['Pentel Ain, Radieră Premium', 'Osaka', StockStatus::InStock, 65, null, '9.50'],
                ['Tombow Mono, Bandă Corectoare', 'Tokyo', StockStatus::InStock, 44, null, '14.50'],
            ]],
            'Birou & Organizare' => ['Papetărie', []],
            'Organizatoare de Birou' => ['Birou & Organizare', [
                ['Suport Pixuri din Lemn de Hinoki', 'Nara', StockStatus::InStock, 14, null, '78.00'],
                ['Organizator Birou Acril, 5 Compartimente', 'Osaka', StockStatus::InStock, 22, null, '64.00'],
                ['Suport Note Adezive din Bambus', 'Kyoto', StockStatus::InStock, 30, null, '35.00'],
            ]],
            'Foarfece & Cuttere' => ['Birou & Organizare', [
                ['Foarfece Allex, Oțel Inoxidabil', 'Tokyo', StockStatus::InStock, 26, null, '46.00'],
                ['Olfa Cutter Profesional', 'Osaka', StockStatus::InStock, 48, null, '27.00'],
                ['Riglă Metalică 30cm, Antiderapantă', 'Nagoya', StockStatus::InStock, 37, null, '19.00'],
            ]],
            'Hârtie' => ['Papetărie', []],
            'Origami' => ['Hârtie', [
                ['Hârtie Origami, 100 coli, Culori Asortate', 'Kyoto', StockStatus::InStock, 90, null, '18.00'],
                ['Hârtie Origami Washi Tradițională, 30 coli', 'Kyoto', StockStatus::InStock, 40, null, '42.00'],
                ['Set Origami pentru Începători + Ghid', 'Tokyo', StockStatus::OnOrder, null, 10, '55.00'],
            ]],
            'Post-it & Notițe' => ['Hârtie', [
                ['Kanmido Pentone, Set Note Adezive', 'Tokyo', StockStatus::InStock, 70, null, '21.00'],
                ['Blocnotes Rhodia A6, Microperforat', 'Osaka', StockStatus::InStock, 33, null, '17.50'],
                ['Notițe Adezive Transparente, Set 6', 'Nagoya', StockStatus::InStock, 58, null, '15.00'],
            ]],
            'Decor & Accesorii' => [null, []],
            'Washi Tape și Decor' => ['Decor & Accesorii', [
                ['MT Washi Tape, Set 5 Modele Sakura', 'Kyoto', StockStatus::InStock, 50, null, '28.00'],
                ['Kamiiso Washi Tape Aurie', 'Kyoto', StockStatus::InStock, 20, null, '19.50'],
                ['Midori Stickere Decorative, Set', 'Tokyo', StockStatus::OnOrder, null, 14, '15.00'],
                ['MT Washi Tape Wide, Model Geometric', 'Kyoto', StockStatus::InStock, 36, null, '24.00'],
                ['Set Stickere Foil Aurii, 8 coli', 'Tokyo', StockStatus::InStock, 47, null, '22.50'],
            ]],
            'Instrumente Tradiționale' => ['Decor & Accesorii', [
                ['Perie Caligrafie Shodo, Bambus', 'Nara', StockStatus::OnOrder, null, 25, '55.00'],
                ['Cerneală Sumi Tradițională, 60ml', 'Nara', StockStatus::OnOrder, null, 25, '48.00'],
                ['Suzuri, Piatră pentru Cerneală', 'Kyoto', StockStatus::InStock, 8, null, '95.00'],
                ['Set Caligrafie Shodo Complet, Cutie Lemn', 'Nara', StockStatus::OnOrder, null, 20, '185.00'],
                ['Hârtie de Orez pentru Caligrafie, 50 coli', 'Kyoto', StockStatus::InStock, 24, null, '38.00'],
            ]],
            'Ștampile & Tușiere' => ['Decor & Accesorii', [
                ['Set Ștampile Cauciuc, Motive Sakura', 'Osaka', StockStatus::InStock, 32, null, '33.00'],
                ['Tușieră StazOn, Negru Permanent', 'Tokyo', StockStatus::InStock, 41, null, '26.00'],
                ['Ștampilă Hanko Personalizată', 'Kyoto', StockStatus::OnOrder, null, 15, '120.00'],
            ]],
            'Artă & Ilustrație' => [null, []],
            'Markere & Fineliner' => ['Artă & Ilustrație', [
                ['Copic Ciao Marker, Set 12 Culori', 'Tokyo', StockStatus::InStock, 16, null, '265.00'],
                ['Sakura Pigma Micron, Set 6 Grosimi', 'Osaka', StockStatus::InStock, 34, null, '72.00'],
                ['Zebra Mildliner, Set 5 Pastel', 'Tokyo', StockStatus::InStock, 60, null, '31.00'],
            ]],
            'Acuarele' => ['Artă & Ilustrație', [
                ['Kuretake Gansai Tambi, 18 Culori', 'Nara', StockStatus::InStock, 12, null, '155.00'],
                ['Pensulă cu Rezervor de Apă, Set 3', 'Osaka', StockStatus::InStock, 28, null, '29.00'],
                ['Bloc Acuarelă Muse, A4, 300g', 'Nagoya', StockStatus::OnOrder, null, 14, '68.00'],
            ]],
            'Blocuri de Schiță' => ['Artă & Ilustrație', [
                ['Bloc Schiță Maruman, A4', 'Tokyo', StockStatus::InStock, 45, null, '36.00'],
                ['Caiet Schițe Copertă Tare, A5', 'Kyoto', StockStatus::InStock, 38, null, '44.00'],
            ]],
            'Cadouri & Sezonier' => [null, []],
            'Seturi Cadou' => ['Cadouri & Sezonier', [
                ['Set Cadou Papetărie Sakura', 'Kyoto', StockStatus::InStock, 18, null, '145.00'],
                ['Cutie Descoperă Japonia, Ediție Papetărie', 'Tokyo', StockStatus::InStock, 10, null, '199.00'],
            ]],
            'Ediții Limitate' => ['Cadouri & Sezonier', [
                ['Agendă Ediție Limitată, Anul Dragonului', 'Tokyo', StockStatus::OnOrder, null, 30, '175.00'],
                ['Set Washi Tape Ediție Toamnă Momiji', 'Kyoto', StockStatus::InStock, 22, null, '52.00'],
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
