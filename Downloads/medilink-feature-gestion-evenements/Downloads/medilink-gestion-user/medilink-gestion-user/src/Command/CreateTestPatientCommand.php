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
    name: 'app:create-test-patient',
    description: 'Crée un patient de test (email: patient@medilink.test, password: patient123)',
)]
final class CreateTestPatientCommand extends Command
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

        $email = 'patient@medilink.test';
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            $io->success('Le patient de test existe déjà : ' . $email);
            $io->note('Connectez-vous avec : patient@medilink.test / patient123');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFullName('Marie Patient');
        $user->setPassword($this->hasher->hashPassword($user, 'patient123'));
        $user->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Patient de test créé !');
        $io->table(
            [' champ ', ' valeur '],
            [
                ['Email', $email],
                ['Mot de passe', 'patient123'],
            ]
        );

        return Command::SUCCESS;
    }
}
