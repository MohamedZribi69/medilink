<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class CreneauUnique extends Constraint
{
    public string $message = 'Un créneau existe déjà pour ce médecin à cette date et sur ce créneau horaire (ou le chevauche).';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
