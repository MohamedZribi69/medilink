<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-medecin',
    description: 'Crée un utilisateur médecin de test pour se connecter (email: medecin@medilink.test, password: medecin123)',
)]
final class CreateTestMedecinCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'medecin@medilink.test';
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            $io->success('Le médecin de test existe déjà : ' . $email);
            $io->note('Connectez-vous avec : medecin@medilink.test / medecin123');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFullName('Dr. Jean Médecin');
        $user->setPassword($this->hasher->hashPassword($user, 'medecin123'));
        $user->setRoles(['ROLE_USER', 'ROLE_MEDECIN']);

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Médecin de test créé !');
        $io->table(
            [' champ ', ' valeur '],
            [
                ['Email', $email],
                ['Mot de passe', 'medecin123'],
                ['URL connexion', '/login'],
            ]
        );

        return Command::SUCCESS;
    }
}
