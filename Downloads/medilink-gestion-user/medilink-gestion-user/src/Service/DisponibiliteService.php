<?php

namespace App\Service;

use App\Entity\Disponibilite;
use App\Entity\User;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Logique métier pour la gestion des disponibilités (médecin).
 */
final class DisponibiliteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DisponibiliteRepository $dispoRepo
    ) {
    }

    /**
     * Crée une disponibilité (après validation du formulaire).
     *
     * @throws \DomainException si chevauchement avec un créneau existant
     */
    public function creerDisponibilite(Disponibilite $disponibilite): Disponibilite
    {
        $medecin = $disponibilite->getMedecin();
        if (!$medecin) {
            throw new \DomainException('Un médecin doit être associé à la disponibilité.');
        }
        $date = $disponibilite->getDate();
        $debut = $disponibilite->getHeureDebut();
        $fin = $disponibilite->getHeureFin();
        if (!$date || !$debut || !$fin) {
            throw new \DomainException('Date et horaires obligatoires.');
        }
        if ($this->dispoRepo->existeChevauchement($medecin, $date, $debut, $fin, null)) {
            throw new \DomainException('Ce créneau chevauche une disponibilité existante.');
        }

        $disponibilite->setStatus(Disponibilite::STATUS_LIBRE);
        $this->em->persist($disponibilite);
        $this->em->flush();

        return $disponibilite;
    }

    /**
     * Vérifie si une disponibilité peut être modifiée.
     */
    public function peutModifier(Disponibilite $disponibilite): bool
    {
        return $disponibilite->getRendezVous() === null;
    }

    /**
     * Vérifie si une disponibilité peut être supprimée.
     */
    public function peutSupprimer(Disponibilite $disponibilite): bool
    {
        return $disponibilite->getRendezVous() === null;
    }

    /**
     * Vérifie si la disponibilité appartient au médecin.
     */
    public function appartientAuMedecin(Disponibilite $disponibilite, User $medecin): bool
    {
        return $disponibilite->getMedecin() === $medecin;
    }

    /**
     * Modifie une disponibilité (après validation du formulaire).
     *
     * @throws \DomainException si réservée ou chevauchement
     */
    public function modifierDisponibilite(Disponibilite $disponibilite): void
    {
        if (!$this->peutModifier($disponibilite)) {
            throw new \DomainException('Impossible de modifier une disponibilité réservée.');
        }

        $medecin = $disponibilite->getMedecin();
        $date = $disponibilite->getDate();
        $debut = $disponibilite->getHeureDebut();
        $fin = $disponibilite->getHeureFin();
        if ($medecin && $date && $debut && $fin && $this->dispoRepo->existeChevauchement($medecin, $date, $debut, $fin, $disponibilite->getId())) {
            throw new \DomainException('Ce créneau chevauche une autre disponibilité.');
        }

        $this->em->flush();
    }

    /**
     * Supprime une disponibilité.
     *
     * @throws \DomainException si déjà réservée
     */
    public function supprimerDisponibilite(Disponibilite $disponibilite): void
    {
        if (!$this->peutSupprimer($disponibilite)) {
            throw new \DomainException('Impossible de supprimer une disponibilité réservée.');
        }
        $this->em->remove($disponibilite);
        $this->em->flush();
    }
}
