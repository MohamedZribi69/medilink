<?php

namespace App\Entity;

use App\Repository\CategoriesDonsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriesDonsRepository::class)]
#[ORM\Table(name: 'categories_dons')]
class CategoriesDons
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icone = 'fa-box';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $couleur = '#3498db';

    #[ORM\OneToMany(targetEntity: Dons::class, mappedBy: 'categorie')]
    private Collection $dons;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->dons = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getIcone(): ?string { return $this->icone; }
    public function setIcone(?string $icone): static { $this->icone = $icone; return $this; }
    public function getCouleur(): ?string { return $this->couleur; }
    public function setCouleur(?string $couleur): static { $this->couleur = $couleur; return $this; }

    /**
     * @return Collection<int, Dons>
     */
    public function getDons(): Collection { return $this->dons; }
    public function addDon(Dons $don): static {
        if (!$this->dons->contains($don)) {
            $this->dons->add($don);
            $don->setCategorie($this);
        }
        return $this;
    }
    public function removeDon(Dons $don): static {
        if ($this->dons->removeElement($don)) {
            if ($don->getCategorie() === $this) {
                $don->setCategorie(null);
            }
        }
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    
    public function __toString(): string { return $this->nom ?? ''; }
}