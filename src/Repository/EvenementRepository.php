<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findAllOrderByDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateEvenement', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Événements à venir ou du jour (pour le front).
     *
     * @return Evenement[]
     */
    public function findUpcoming(): array
    {
        $today = (new \DateTime())->setTime(0, 0, 0);

        return $this->createQueryBuilder('e')
            ->andWhere('e.dateEvenement >= :today')
            ->setParameter('today', $today)
            ->orderBy('e.dateEvenement', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
