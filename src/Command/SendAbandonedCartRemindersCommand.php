<?php

namespace App\Command;

use App\Repository\CartRepository;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * De rulat periodic printr-un cron extern (nu avem symfony/scheduler
 * instalat) — de exemplu o dată pe oră.
 */
#[AsCommand(name: 'app:send-abandoned-cart-reminders', description: 'Trimite un email de reminder clienților cu coșuri abandonate')]
final class SendAbandonedCartRemindersCommand extends Command
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly StoreConfig $store,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('min-hours', null, InputOption::VALUE_REQUIRED, 'Vârsta minimă (ore) a coșului neatins pentru a trimite reminder', 24)
            ->addOption('max-days', null, InputOption::VALUE_REQUIRED, 'Nu mai trimite reminder pentru coșuri mai vechi de atât (zile)', 7)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $olderThan = new \DateTimeImmutable(sprintf('-%d hours', (int) $input->getOption('min-hours')));
        $newerThan = new \DateTimeImmutable(sprintf('-%d days', (int) $input->getOption('max-days')));

        $carts = $this->cartRepository->findAbandoned($olderThan, $newerThan);

        foreach ($carts as $cart) {
            $user = $cart->getUser();
            if (!$user) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->from(new Address($this->store->email, $this->store->name))
                ->to($user->getEmail())
                ->subject(sprintf('Ai uitat ceva în coș — %s', $this->store->name))
                ->htmlTemplate('emails/abandoned_cart.html.twig')
                ->context([
                    'user' => $user,
                    'cart' => $cart,
                    'cartUrl' => $this->urlGenerator->generate('app_cart', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ])
            ;
            $this->mailer->send($email);

            $cart->markReminderSent();
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d remindere de coș abandonat trimise.', count($carts)));

        return Command::SUCCESS;
    }
}
