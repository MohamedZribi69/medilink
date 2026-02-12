<?php

namespace App\Validator;

use App\Entity\Disponibilite;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validateur pour les règles métier de Disponibilite.
 * Utilisé via validation.yaml (pas de validation dans l'entité).
 */
final class DisponibiliteValidator
{
    public static function validate(Disponibilite $dispo, ExecutionContextInterface $context): void
    {
        self::validateHeureFinApresDebut($dispo, $context);
        self::validateDateNonPassee($dispo, $context);
    }

    private static function validateHeureFinApresDebut(Disponibilite $dispo, ExecutionContextInterface $context): void
    {
        if ($dispo->getHeureDebut() === null || $dispo->getHeureFin() === null) {
            return;
        }
        if ($dispo->getHeureFin() <= $dispo->getHeureDebut()) {
            $context->buildViolation('L\'heure de fin doit être postérieure à l\'heure de début.')
                ->atPath('heureFin')
                ->addViolation();
        }
    }

    private static function validateDateNonPassee(Disponibilite $dispo, ExecutionContextInterface $context): void
    {
        if ($dispo->getDate() === null || $dispo->getId() !== null) {
            return;
        }
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        if ($dispo->getDate()->format('Y-m-d') < $today) {
            $context->buildViolation('La date doit être aujourd\'hui ou une date future.')
                ->atPath('date')
                ->addViolation();
        }
    }
}
