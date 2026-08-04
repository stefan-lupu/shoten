<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Creează un utilizator cu ROLE_ADMIN')]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('firstName', InputArgument::OPTIONAL, default: 'Admin')
            ->addArgument('lastName', InputArgument::OPTIONAL, default: 'Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existing = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $input->getArgument('email')]);
        if ($existing) {
            $io->error('Există deja un cont cu acest email.');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($input->getArgument('email'));
        $user->setFirstName($input->getArgument('firstName'));
        $user->setLastName($input->getArgument('lastName'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->getArgument('password')));
        // Creat direct de developer prin CLI — nu are sens să treacă prin verificarea de email.
        $user->setVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin creat: %s', $user->getEmail()));

        return Command::SUCCESS;
    }
}
