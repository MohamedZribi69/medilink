<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-admin',
    description: 'Crée un administrateur de test (email: admin@medilink.test, password: admin123)',
)]
final class CreateTestAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('promote', 'p', InputOption::VALUE_REQUIRED, 'Promouvoir un utilisateur existant (email) en admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $promoteEmail = $input->getOption('promote');

        if ($promoteEmail) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $promoteEmail]);
            if (!$user) {
                $io->error('Utilisateur non trouvé : ' . $promoteEmail);
                return Command::FAILURE;
            }
            $roles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true)) {
                $io->success($promoteEmail . ' est déjà administrateur.');
                return Command::SUCCESS;
            }
            $user->setRoles(array_merge($roles, ['ROLE_ADMIN']));
            $this->em->flush();
            $io->success($promoteEmail . ' est maintenant administrateur.');
            return Command::SUCCESS;
        }

        $email = 'admin@medilink.test';
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            $existing->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            $this->em->flush();
            $io->success('L\'admin de test existe déjà : ' . $email);
            $io->note('Connectez-vous avec : admin@medilink.test / admin123');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFullName('Admin MediLink');
        $user->setPassword($this->hasher->hashPassword($user, 'admin123'));
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Administrateur de test créé !');
        $io->table(
            [' champ ', ' valeur '],
            [
                ['Email', $email],
                ['Mot de passe', 'admin123'],
                ['URL admin', '/admin/'],
            ]
        );
        return Command::SUCCESS;
    }
}
