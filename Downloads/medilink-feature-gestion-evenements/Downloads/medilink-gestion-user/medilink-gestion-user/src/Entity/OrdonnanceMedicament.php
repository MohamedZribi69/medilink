<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'ordonnance_medicament')]
class OrdonnanceMedicament
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ordonnanceMedicaments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ordonnance $ordonnance = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ordonnanceMedicaments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Medicament $medicament = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(1)]
    private int $quantite = 1;

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): self
    {
        $this->ordonnance = $ordonnance;
        return $this;
    }

    public function getMedicament(): ?Medicament
    {
        return $this->medicament;
    }

    public function setMedicament(?Medicament $medicament): self
    {
        $this->medicament = $medicament;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }
}
