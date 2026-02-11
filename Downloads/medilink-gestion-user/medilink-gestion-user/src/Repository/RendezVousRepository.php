<?php

namespace App\Repository;

use App\Entity\RendezVous;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * @return RendezVous[]
     */
    public function findAllOrderByDate(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->orderBy('r.dateHeure', 'DESC')
            ->addOrderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rendez-vous à venir (dateHeure >= maintenant).
     *
     * @return RendezVous[]
     */
    public function findUpcoming(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.medecin', 'dm')->addSelect('dm')
            ->andWhere('r.dateHeure >= :now')
            ->andWhere('r.statut != :annule')
            ->setParameter('now', $now)
            ->setParameter('annule', RendezVous::STATUT_ANNULE)
            ->orderBy('r.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec filtres (statut, ordre) et recherche textuelle (patient, médecin, motif).
     *
     * @return RendezVous[]
     */
    public function search(?string $q, ?string $statut, ?string $ordre = 'desc'): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.medecin', 'dm')->addSelect('dm')
            ->leftJoin('r.patient', 'p')->addSelect('p');

        if ($statut !== '' && $statut !== null) {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        if ($q !== '' && $q !== null) {
            $qb->andWhere(
                'LOWER(p.fullName) LIKE :q OR LOWER(dm.fullName) LIKE :q OR LOWER(r.motif) LIKE :q'
            )->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $orderDir = ($ordre === 'asc') ? 'ASC' : 'DESC';
        $qb->orderBy('r.dateHeure', $orderDir)->addOrderBy('r.id', $orderDir);

        return $qb->getQuery()->getResult();
    }

    /**
     * Rendez-vous d'un médecin (via ses disponibilités).
     *
     * @return RendezVous[]
     */
    public function findByMedecin(User $medecin): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('r.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rendez-vous d'un médecin avec recherche (patient, motif) et filtres.
     *
     * @return RendezVous[]
     */
    public function searchByMedecin(User $medecin, ?string $q = '', ?string $statut = null, ?string $ordre = 'desc'): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('r.patient', 'p')->addSelect('p')
            ->andWhere('d.medecin = :medecin')
            ->setParameter('medecin', $medecin);

        if ($statut !== '' && $statut !== null) {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        if ($q !== '' && $q !== null) {
            $qb->andWhere(
                'LOWER(p.fullName) LIKE :q OR LOWER(r.motif) LIKE :q'
            )->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $orderDir = ($ordre === 'asc') ? 'ASC' : 'DESC';
        $qb->orderBy('r.dateHeure', $orderDir)->addOrderBy('r.id', $orderDir);

        return $qb->getQuery()->getResult();
    }

    /**
     * Rendez-vous d'un patient donné.
     *
     * @return RendezVous[]
     */
    public function findByPatient(User $patient): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.medecin', 'dm')->addSelect('dm')
            ->andWhere('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
