<?php

namespace App\Service;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Logique métier du module Rendez-vous.
 * Encapsule les règles : une disponibilité = un seul rendez-vous, statuts, etc.
 */
final class RendezVousService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Vérifie si une disponibilité peut être réservée pour un rendez-vous.
     */
    public function peutReserver(Disponibilite $disponibilite): bool
    {
        return $disponibilite->estLibre();
    }

    /**
     * Crée un rendez-vous à partir d'une disponibilité libre.
     * Règle métier : la disponibilité passe en "réservée", liaison bidirectionnelle.
     *
     * @throws \DomainException si la disponibilité n'est plus libre
     */
    public function creerRendezVous(
        Disponibilite $disponibilite,
        ?User $patient = null,
        string $statut = RendezVous::STATUT_EN_ATTENTE,
        ?string $motif = null
    ): RendezVous {
        $dispo = $this->em->find(Disponibilite::class, $disponibilite->getId());
        if (!$dispo) {
            throw new \DomainException('Disponibilité introuvable.');
        }
        if (!$dispo->estLibre()) {
            throw new \DomainException('Cette disponibilité n\'est plus libre (déjà réservée).');
        }

        $rdv = new RendezVous();
        $rdv->setDisponibilite($dispo);
        $rdv->setDateHeure($dispo->getDateHeureRendezVous());
        $rdv->setPatient($patient);
        $rdv->setStatut($statut);
        $rdv->setMotif($motif);

        $dispo->setStatus(Disponibilite::STATUS_RESERVEE);
        $dispo->setRendezVous($rdv);

        $this->em->persist($rdv);
        $this->em->flush();

        return $rdv;
    }

    /**
     * Annule / supprime un rendez-vous et libère la disponibilité associée.
     */
    public function annulerRendezVous(RendezVous $rendezVous): void
    {
        $dispo = $rendezVous->getDisponibilite();
        if ($dispo) {
            $dispo->setStatus(Disponibilite::STATUS_LIBRE);
            $dispo->setRendezVous(null);
        }
        $this->em->remove($rendezVous);
        $this->em->flush();
    }

    /**
     * Confirme un rendez-vous (changement de statut).
     */
    public function confirmerRendezVous(RendezVous $rendezVous): void
    {
        $rendezVous->setStatut(RendezVous::STATUT_CONFIRME);
        $this->em->flush();
    }

    /**
     * Marque un rendez-vous comme terminé.
     */
    public function marquerTermine(RendezVous $rendezVous): void
    {
        $rendezVous->setStatut(RendezVous::STATUT_TERMINE);
        $this->em->flush();
    }

    /**
     * Vérifie si un rendez-vous appartient au médecin (via sa disponibilité).
     */
    public function appartientAuMedecin(RendezVous $rendezVous, User $medecin): bool
    {
        $dispo = $rendezVous->getDisponibilite();
        return $dispo && $dispo->getMedecin() === $medecin;
    }
}
