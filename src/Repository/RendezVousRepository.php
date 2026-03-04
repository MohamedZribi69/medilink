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
        return $this->getSearchQueryBuilder($q, $statut, $ordre)->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour la recherche (pour pagination).
     */
    public function getSearchQueryBuilder(?string $q, ?string $statut, ?string $ordre = 'desc'): \Doctrine\ORM\QueryBuilder
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

        return $qb;
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
        return $this->getSearchByMedecinQueryBuilder($medecin, $q, $statut, $ordre)->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour searchByMedecin (pagination).
     */
    public function getSearchByMedecinQueryBuilder(User $medecin, ?string $q = '', ?string $statut = null, ?string $ordre = 'desc'): \Doctrine\ORM\QueryBuilder
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

        return $qb;
    }

    /**
     * Rendez-vous d'un patient donné.
     *
     * @return RendezVous[]
     */
    public function findByPatient(User $patient): array
    {
        return $this->getFindByPatientQueryBuilder($patient)->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour findByPatient (pagination).
     */
    public function getFindByPatientQueryBuilder(User $patient): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')->addSelect('d')
            ->leftJoin('d.medecin', 'dm')->addSelect('dm')
            ->andWhere('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.dateHeure', 'DESC')
            ->addOrderBy('r.id', 'DESC');
    }

    /**
     * Retourne les IDs des médecins très demandés (>= $minCount RDV depuis $from).
     *
     * @return int[]
     */
    public function findHotMedecinIds(\DateTimeInterface $from, int $minCount = 10): array
    {
        $rows = $this->createQueryBuilder('r')
            ->join('r.disponibilite', 'd')
            ->join('d.medecin', 'm')
            ->select('m.id AS medecin_id, COUNT(r.id) AS rdv_count')
            ->andWhere('r.dateHeure >= :from')
            ->setParameter('from', $from)
            ->groupBy('medecin_id')
            ->having('COUNT(r.id) >= :minCount')
            ->setParameter('minCount', $minCount)
            ->orderBy('rdv_count', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['medecin_id'], $rows);
    }
}
