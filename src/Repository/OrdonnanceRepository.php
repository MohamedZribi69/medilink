<?php

namespace App\Repository;

use App\Entity\Ordonnance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ordonnance>
 */
class OrdonnanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ordonnance::class);
    }

    /** @return Ordonnance[] */
    public function findAllOrderByDate(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.medecin', 'med')->addSelect('med')
            ->leftJoin('o.patient', 'pat')->addSelect('pat')
            ->leftJoin('o.ordonnanceMedicaments', 'om')->addSelect('om')
            ->leftJoin('om.medicament', 'm')->addSelect('m')
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Ordonnance[] */
    public function findByMedecin(User $medecin): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.patient', 'pat')->addSelect('pat')
            ->leftJoin('o.ordonnanceMedicaments', 'om')->addSelect('om')
            ->leftJoin('om.medicament', 'm')->addSelect('m')
            ->andWhere('o.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Ordonnance[] */
    public function findByPatient(User $patient): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.medecin', 'med')->addSelect('med')
            ->leftJoin('o.ordonnanceMedicaments', 'om')->addSelect('om')
            ->leftJoin('om.medicament', 'm')->addSelect('m')
            ->andWhere('o.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Ordonnances du patient contenant le médicament donné.
     * @return Ordonnance[]
     */
    public function findByPatientAndMedicament(User $patient, int $medicamentId): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.medecin', 'med')->addSelect('med')
            ->leftJoin('o.ordonnanceMedicaments', 'om')->addSelect('om')
            ->leftJoin('om.medicament', 'm')->addSelect('m')
            ->andWhere('o.patient = :patient')
            ->andWhere('m.id = :mid')
            ->setParameter('patient', $patient)
            ->setParameter('mid', $medicamentId)
            ->orderBy('o.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
