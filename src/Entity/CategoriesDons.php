<?php

namespace App\Entity;

use App\Repository\CategoriesDonsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoriesDonsRepository::class)]
#[ORM\Table(name: 'categories_dons')]
class CategoriesDons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    /** @var Collection<int, Dons> */
    #[ORM\OneToMany(targetEntity: Dons::class, mappedBy: 'categorie')]
    private Collection $dons;

    public function __construct()
    {
        $this->dons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /** @return Collection<int, Dons> */
    public function getDons(): Collection
    {
        return $this->dons;
    }

    public function __toString(): string
    {
        return (string) $this->nom;
    }
}
