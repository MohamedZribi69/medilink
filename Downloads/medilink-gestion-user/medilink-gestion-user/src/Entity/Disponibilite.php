<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
#[ORM\Table(name: 'disponibilites')]
class Disponibilite
{
    public const STATUS_LIBRE = 'libre';
    public const STATUS_RESERVEE = 'reservee';
    public const STATUS_ANNULEE = 'annulee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_LIBRE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $medecin = null;

    #[ORM\OneToOne(targetEntity: RendezVous::class, mappedBy: 'disponibilite', cascade: ['persist', 'remove'])]
    private ?RendezVous $rendezVous = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMedecin(): ?User
    {
        return $this->medecin;
    }

    public function setMedecin(?User $medecin): self
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getRendezVous(): ?RendezVous
    {
        return $this->rendezVous;
    }

    public function setRendezVous(?RendezVous $rendezVous): self
    {
        $this->rendezVous = $rendezVous;
        return $this;
    }

    public function estLibre(): bool
    {
        return $this->status === self::STATUS_LIBRE;
    }

    public static function getStatuts(): array
    {
        return [
            'Libre' => self::STATUS_LIBRE,
            'Réservée' => self::STATUS_RESERVEE,
            'Annulée' => self::STATUS_ANNULEE,
        ];
    }

    /** Retourne date_heure pour un rendez-vous (combinaison date + heure_debut). */
    public function getDateHeureRendezVous(): ?\DateTimeInterface
    {
        if ($this->date === null || $this->heureDebut === null) {
            return null;
        }
        $d = \DateTime::createFromInterface($this->date);
        $h = \DateTime::createFromInterface($this->heureDebut);
        $d->setTime((int) $h->format('H'), (int) $h->format('i'), (int) $h->format('s'));
        return $d;
    }
}
