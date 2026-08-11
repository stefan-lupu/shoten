<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Debifează flagul „promovat" al produselor a căror perioadă de promovare
 * s-a încheiat (promotedUntil în trecut). De rulat periodic printr-un cron
 * extern (nu avem symfony/scheduler) — de exemplu o dată pe oră.
 *
 * Afișarea folosește oricum Product::isCurrentlyPromoted() (care respectă
 * fereastra), deci o promovare expirată nu apare promovată nici între rulări;
 * comanda doar face curat în flag, ca bifa din admin să reflecte realitatea.
 */
#[AsCommand(name: 'app:expire-promotions', description: 'Debifează produsele cu promovare expirată')]
final class ExpirePromotionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        $expired = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->andWhere('p.isPromoted = true')
            ->andWhere('p.promotedUntil IS NOT NULL')
            ->andWhere('p.promotedUntil < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        foreach ($expired as $product) {
            $product->setIsPromoted(false);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d promovări expirate au fost debifate.', \count($expired)));

        return Command::SUCCESS;
    }
}
