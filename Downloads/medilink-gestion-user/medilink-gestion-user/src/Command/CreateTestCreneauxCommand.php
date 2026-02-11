<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Disponibilite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-creneaux',
    description: 'Crée des disponibilités de test pour le médecin medecin@medilink.test',
)]
final class CreateTestCreneauxCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $medecin = $this->em->getRepository(User::class)->findOneBy(['email' => 'medecin@medilink.test']);
        if (!$medecin) {
            $io->error('Médecin de test introuvable. Exécutez d\'abord : php bin/console app:create-test-medecin');
            return Command::FAILURE;
        }

        $today = new \DateTime('today');
        $creneaux = [
            [$today, '09:00', '10:00'],
            [$today, '10:30', '11:30'],
            [(clone $today)->modify('+1 day'), '09:00', '10:00'],
            [(clone $today)->modify('+1 day'), '14:00', '15:00'],
            [(clone $today)->modify('+2 days'), '08:00', '09:00'],
        ];

        $count = 0;
        foreach ($creneaux as [$date, $debut, $fin]) {
            [$h, $m] = explode(':', $debut);
            $heureDebut = (clone $date)->setTime((int) $h, (int) $m, 0);
            [$h, $m] = explode(':', $fin);
            $heureFin = (clone $date)->setTime((int) $h, (int) $m, 0);

            $existe = $this->em->getRepository(Disponibilite::class)->findOneBy([
                'medecin' => $medecin,
                'date' => $date,
                'heureDebut' => $heureDebut,
            ]);
            if ($existe) {
                continue;
            }

            $d = new Disponibilite();
            $d->setMedecin($medecin);
            $d->setDate($date);
            $d->setHeureDebut($heureDebut);
            $d->setHeureFin($heureFin);
            $d->setStatus(Disponibilite::STATUS_LIBRE);
            $this->em->persist($d);
            $count++;
        }

        $this->em->flush();

        $io->success("$count disponibilité(s) de test créée(s).");
        $io->note('Connectez-vous en tant que patient pour les réserver.');

        return Command::SUCCESS;
    }
}
