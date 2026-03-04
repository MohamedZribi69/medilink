<?php

namespace App\Validator;

use App\Entity\Disponibilite;
use App\Repository\DisponibiliteRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class CreneauUniqueValidator extends ConstraintValidator
{
    public function __construct(
        private DisponibiliteRepository $disponibiliteRepository
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CreneauUnique) {
            throw new UnexpectedTypeException($constraint, CreneauUnique::class);
        }

        if (!$value instanceof Disponibilite) {
            return;
        }

        $medecin = $value->getMedecin();
        $date = $value->getDate();
        $heureDebut = $value->getHeureDebut();
        $heureFin = $value->getHeureFin();

        if ($medecin === null || $date === null || $heureDebut === null || $heureFin === null) {
            return;
        }

        $excludeId = $value->getId();

        if ($this->disponibiliteRepository->existeChevauchement($medecin, $date, $heureDebut, $heureFin, $excludeId)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('date')
                ->addViolation();
        }
    }
}
