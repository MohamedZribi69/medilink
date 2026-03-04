<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'ordonnance_medicament')]
#[Assert\Callback('validateQuantiteStock')]
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

    public function validateQuantiteStock(ExecutionContextInterface $context): void
    {
        if ($this->medicament === null) {
            return;
        }
        $stock = $this->medicament->getQuantiteStock();
        if ($this->quantite > $stock) {
            $context->buildViolation('La quantité ne peut pas dépasser le stock disponible ({{ stock }} pour {{ medicament }}).')
                ->setParameter('{{ stock }}', (string) $stock)
                ->setParameter('{{ medicament }}', $this->medicament->getNom() ?? 'ce médicament')
                ->atPath('quantite')
                ->addViolation();
        }
    }

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
