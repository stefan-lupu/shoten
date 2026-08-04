<?php

namespace App\Command;

use App\Entity\Campaign;
use App\Entity\CampaignProduct;
use App\Entity\Product;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;
use App\Enum\DiscountValueType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-campaigns', description: 'Populează campanii de test, câte una pentru fiecare tip')]
final class SeedCampaignsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $product = fn (string $slug): Product => $this->productRepository->findOneBy(['slug' => $slug])
            ?? throw new \RuntimeException("Produs lipsă (rulează mai întâi app:seed-catalog): {$slug}");

        $pilotG2 = $product('pilot-g2-retractable-0-7mm');
        $midoriNotebook = $product('midori-md-notebook-a5-liniat');
        $kokuyoCaiet = $product('kokuyo-campus-caiet-set-5-buc');
        $mtWashiTape = $product('mt-washi-tape-set-5-modele-sakura');
        $kamiisoWashiTape = $product('kamiiso-washi-tape-aurie');

        $percentage = (new Campaign())
            ->setName('10% reducere la Pilot G2')
            ->setType(CampaignType::Discount)
            ->setDiscountValueType(DiscountValueType::Percentage)
            ->setDiscountValue('10.00')
        ;
        $percentage->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($percentage)->setProduct($pilotG2)->setRole(CampaignProductRole::Target)
        );

        $fixed = (new Campaign())
            ->setName('15 lei reducere la orice comandă')
            ->setType(CampaignType::Discount)
            ->setDiscountValueType(DiscountValueType::Fixed)
            ->setDiscountValue('15.00')
        ;

        $coupon = (new Campaign())
            ->setName('Cod PRIMAVARA20')
            ->setType(CampaignType::Coupon)
            ->setCouponCode('PRIMAVARA20')
            ->setDiscountValue('20.00')
            ->setMaxUses(100)
        ;

        $bogo = (new Campaign())
            ->setName('Cumperi Kokuyo Campus, primești Washi Tape MT gratis')
            ->setType(CampaignType::Bogo)
        ;
        $bogo->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($bogo)->setProduct($kokuyoCaiet)->setRole(CampaignProductRole::Trigger)
        );
        $bogo->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($bogo)->setProduct($mtWashiTape)->setRole(CampaignProductRole::Gift)
        );

        $giftThreshold = (new Campaign())
            ->setName('Cadou Kamiiso Washi Tape la comenzi peste 100 lei')
            ->setType(CampaignType::GiftThreshold)
            ->setDiscountValue('100.00')
        ;
        $giftThreshold->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($giftThreshold)->setProduct($kamiisoWashiTape)->setRole(CampaignProductRole::Gift)
        );

        $bundle = (new Campaign())
            ->setName('Bundle scris: Pilot G2 + Midori MD Notebook')
            ->setType(CampaignType::Bundle)
            ->setDiscountValue('10.00')
        ;
        $bundle->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($bundle)->setProduct($pilotG2)->setRole(CampaignProductRole::BundleItem)
        );
        $bundle->getCampaignProducts()->add(
            (new CampaignProduct())->setCampaign($bundle)->setProduct($midoriNotebook)->setRole(CampaignProductRole::BundleItem)
        );

        foreach ([$percentage, $fixed, $coupon, $bogo, $giftThreshold, $bundle] as $campaign) {
            $this->entityManager->persist($campaign);
        }
        $this->entityManager->flush();

        $io->success('6 campanii de test create (discount ×2, coupon, bogo, gift_threshold, bundle).');

        return Command::SUCCESS;
    }
}
