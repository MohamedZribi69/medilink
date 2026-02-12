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

    // Méthodes personnalisées
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

    /**
     * Recherche avancée pour le tableau de bord admin
     *
     * @return Dons[]
     */
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
            $qb->andWhere('d.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($categorieId) {
            $qb->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        if ($urgence) {
            $qb->andWhere('d.niveauUrgence = :urgence')
               ->setParameter('urgence', $urgence);
        }

        if ($search) {
            $qb->andWhere('LOWER(d.articleDescription) LIKE :search OR LOWER(d.detailsSupplementaires) LIKE :search')
               ->setParameter('search', '%'.mb_strtolower($search, 'UTF-8').'%');
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        switch ($sort) {
            case 'quantite':
                $qb->orderBy('d.quantite', $direction);
                break;
            case 'categorie':
                $qb->orderBy('c.nom', $direction);
                break;
            case 'urgence':
                $qb->orderBy('d.niveauUrgence', $direction);
                break;
            default:
                $qb->orderBy('d.dateSoumission', $direction);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques : nombre de dons validés par catégorie
     *
     * @return array<array{id:int, nom:string, total:int}>
     */
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

    /**
     * Recherche pour le front (liste des dons disponibles)
     * - toujours limité aux dons validés
     *
     * @return Dons[]
     */
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
            $qb->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        if ($urgence) {
            $qb->andWhere('d.niveauUrgence = :urgence')
               ->setParameter('urgence', $urgence);
        }

        if ($search) {
            $qb->andWhere('LOWER(d.articleDescription) LIKE :search OR LOWER(d.detailsSupplementaires) LIKE :search')
               ->setParameter('search', '%'.mb_strtolower($search, 'UTF-8').'%');
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        switch ($sort) {
            case 'quantite':
                $qb->orderBy('d.quantite', $direction);
                break;
            case 'categorie':
                $qb->orderBy('c.nom', $direction);
                break;
            case 'urgence':
                $qb->orderBy('d.niveauUrgence', $direction);
                break;
            default:
                $qb->orderBy('d.dateSoumission', $direction);
        }

        return $qb->getQuery()->getResult();
    }
}