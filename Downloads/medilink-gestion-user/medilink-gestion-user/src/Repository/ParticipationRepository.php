<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * @return Participation[]
     */
    public function findByEvenement(Evenement $evenement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :ev')
            ->setParameter('ev', $evenement)
            ->orderBy('p.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndEvenement(User $user, Evenement $evenement): ?Participation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.evenement = :ev')
            ->setParameter('user', $user)
            ->setParameter('ev', $evenement)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
