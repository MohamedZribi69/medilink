<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Disponibilite>
 */
class DisponibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disponibilite::class);
    }

    /**
     * @return Disponibilite[]
     */
    public function findAllOrderByDate(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Disponibilités libres (pour créer un rendez-vous).
     *
     * @return Disponibilite[]
     */
    public function findLibres(?\DateTimeInterface $from = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.status = :libre')
            ->setParameter('libre', Disponibilite::STATUS_LIBRE)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC');

        if ($from !== null) {
            $qb->andWhere('d.date >= :from')->setParameter('from', $from);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Disponibilités libres à partir d'aujourd'hui.
     *
     * @return Disponibilite[]
     */
    public function findLibresAvenir(): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :libre')
            ->andWhere('d.date >= :today')
            ->setParameter('libre', Disponibilite::STATUS_LIBRE)
            ->setParameter('today', $today)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Disponibilités libres à venir, avec infos médecin (pour le patient).
     *
     * @return Disponibilite[]
     */
    public function findLibresAvenirAvecMedecin(): array
    {
        $today = new \DateTime('today');

        return $this->createQueryBuilder('d')
            ->leftJoin('d.medecin', 'm')->addSelect('m')
            ->andWhere('d.status = :libre')
            ->andWhere('d.date >= :today')
            ->andWhere('d.medecin IS NOT NULL')
            ->setParameter('libre', Disponibilite::STATUS_LIBRE)
            ->setParameter('today', $today)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Disponibilités réservables pour un patient :
     * - libres
     * - à venir (date >= aujourd'hui)
     * - associées à un médecin.
     *
     * @return Disponibilite[]
     */
    public function findAvailableForPatient(): array
    {
        return $this->findLibresAvenirAvecMedecin();
    }

    /**
     * Vérifie si une date/heure est déjà réservée pour un médecin (chevauchement).
     */
    public function existeChevauchement(\App\Entity\User $medecin, \DateTimeInterface $date, \DateTimeInterface $heureDebut, \DateTimeInterface $heureFin, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.medecin = :medecin')
            ->andWhere('d.date = :date')
            ->andWhere('d.heureDebut < :heureFin AND d.heureFin > :heureDebut')
            ->setParameter('medecin', $medecin)
            ->setParameter('date', $date)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin);

        if ($excludeId !== null) {
            $qb->andWhere('d.id != :exclude')->setParameter('exclude', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Disponibilités du médecin.
     *
     * @return Disponibilite[]
     */
    public function findByMedecin(\App\Entity\User $medecin): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Disponibilités du médecin avec filtres (statut, ordre).
     *
     * @return Disponibilite[]
     */
    public function searchByMedecin(\App\Entity\User $medecin, ?string $statut, ?string $ordre = 'asc'): array
    {
        return $this->getSearchByMedecinQueryBuilder($medecin, $statut, $ordre)->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour searchByMedecin (pagination).
     */
    public function getSearchByMedecinQueryBuilder(\App\Entity\User $medecin, ?string $statut, ?string $ordre = 'asc'): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin);

        if ($statut !== '' && $statut !== null) {
            $qb->andWhere('d.status = :statut')->setParameter('statut', $statut);
        }

        $orderDir = ($ordre === 'desc') ? 'DESC' : 'ASC';
        $qb->orderBy('d.date', $orderDir)
            ->addOrderBy('d.heureDebut', $orderDir)
            ->addOrderBy('d.id', $orderDir);

        return $qb;
    }
}
