<?php

namespace App\Repository;

use App\Entity\Dons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dons>
 */
class DonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dons::class);
    }

    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('d.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Dons $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dons $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchForAdmin(
        ?string $statut,
        ?int $categorieId,
        ?string $urgence,
        ?string $search,
        string $sort = 'date',
        string $direction = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.categorie', 'c')
            ->addSelect('c');
        if ($statut && $statut !== 'tous') {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $statut);
        }
        if ($categorieId) {
            $qb->andWhere('c.id = :categorieId')->setParameter('categorieId', $categorieId);
        }
        if ($urgence) {
            $qb->andWhere('d.niveauUrgence = :urgence')->setParameter('urgence', $urgence);
        }
        if ($search) {
            $qb->andWhere('LOWER(d.articleDescription) LIKE :search OR LOWER(d.detailsSupplementaires) LIKE :search')
               ->setParameter('search', '%'.mb_strtolower($search, 'UTF-8').'%');
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        switch ($sort) {
            case 'quantite': $qb->orderBy('d.quantite', $direction); break;
            case 'categorie': $qb->orderBy('c.nom', $direction); break;
            case 'urgence': $qb->orderBy('d.niveauUrgence', $direction); break;
            default: $qb->orderBy('d.dateSoumission', $direction);
        }
        return $qb->getQuery()->getResult();
    }

    public function getValidCountsByCategory(): array
    {
        return $this->createQueryBuilder('d')
            ->select('c.id AS id, c.nom AS nom, COUNT(d.id) AS total')
            ->join('d.categorie', 'c')
            ->where('d.statut = :statut')
            ->setParameter('statut', Dons::STATUT_VALIDE)
            ->groupBy('c.id, c.nom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function searchForFront(
        ?int $categorieId,
        ?string $urgence,
        ?string $search,
        string $sort = 'date',
        string $direction = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.categorie', 'c')
            ->addSelect('c')
            ->where('d.statut = :statut')
            ->setParameter('statut', Dons::STATUT_VALIDE);
        if ($categorieId) {
            $qb->andWhere('c.id = :categorieId')->setParameter('categorieId', $categorieId);
        }
        if ($urgence) {
            $qb->andWhere('d.niveauUrgence = :urgence')->setParameter('urgence', $urgence);
        }
        if ($search) {
            $qb->andWhere('LOWER(d.articleDescription) LIKE :search OR LOWER(d.detailsSupplementaires) LIKE :search')
               ->setParameter('search', '%'.mb_strtolower($search, 'UTF-8').'%');
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        switch ($sort) {
            case 'quantite': $qb->orderBy('d.quantite', $direction); break;
            case 'categorie': $qb->orderBy('c.nom', $direction); break;
            case 'urgence': $qb->orderBy('d.niveauUrgence', $direction); break;
            default: $qb->orderBy('d.dateSoumission', $direction);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Évolution mensuelle sur les N derniers mois (toujours N mois pour la courbe).
     */
    public function getMonthlyEvolution(int $months = 12): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE_FORMAT(d.date_soumission, '%Y-%m') AS period, COUNT(d.id) AS total,
                SUM(CASE WHEN d.statut = 'valide' THEN 1 ELSE 0 END) AS valides,
                SUM(CASE WHEN d.statut = 'rejete' THEN 1 ELSE 0 END) AS rejetes,
                SUM(CASE WHEN d.statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente
                FROM dons d WHERE d.date_soumission >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY period ORDER BY period ASC";
        $result = $conn->executeQuery($sql, ['months' => $months])->fetchAllAssociative();
        $byPeriod = [];
        foreach ($result as $row) {
            $byPeriod[$row['period']] = [
                'total' => max(0, (int) $row['total']),
                'valides' => max(0, (int) $row['valides']),
                'rejetes' => max(0, (int) $row['rejetes']),
                'en_attente' => max(0, (int) $row['en_attente']),
            ];
        }
        $mois = ['', 'Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];
        $out = [];
        $now = new \DateTimeImmutable();
        for ($i = $months - 1; $i >= 0; $i--) {
            $dt = $now->modify("-$i months");
            $period = $dt->format('Y-m');
            $m = (int) $dt->format('n');
            $label = ($mois[$m] ?? $m) . ' ' . $dt->format('Y');
            $data = $byPeriod[$period] ?? ['total' => 0, 'valides' => 0, 'rejetes' => 0, 'en_attente' => 0];
            $out[] = [
                'label' => $label,
                'total' => max(0, $data['total']),
                'valides' => max(0, $data['valides']),
                'rejetes' => max(0, $data['rejetes']),
                'en_attente' => max(0, $data['en_attente']),
            ];
        }
        return $out;
    }

    public function getCountByCategory(): array
    {
        return $this->createQueryBuilder('d')
            ->select('c.nom AS nom, c.couleur AS couleur, COUNT(d.id) AS total')
            ->join('d.categorie', 'c')
            ->groupBy('c.id, c.nom, c.couleur')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    public function getValidationRate(): float
    {
        $valides = $this->count(['statut' => Dons::STATUT_VALIDE]);
        $rejetes = $this->count(['statut' => Dons::STATUT_REJETE]);
        $total = $valides + $rejetes;
        return $total === 0 ? 0.0 : round(100.0 * $valides / $total, 1);
    }
}
