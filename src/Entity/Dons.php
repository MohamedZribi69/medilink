<?php

namespace App\Entity;

use App\Repository\DonsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DonsRepository::class)]
#[ORM\Table(name: 'dons')]
class Dons
{
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDE = 'valide';
    const STATUT_REJETE = 'rejete';
    const STATUT_ANNULE = 'annule';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, name: 'categorie_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    private ?CategoriesDons $categorie = null;

    #[ORM\Column(length: 255, name: 'article_description')]
    #[Assert\NotBlank(message: 'La description de l\'article est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $articleDescription = null;

    #[ORM\Column(name: 'quantite')]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être un nombre strictement positif.')]
    private ?int $quantite = null;

    #[ORM\Column(length: 20, nullable: true, name: 'unite')]
    private ?string $unite = 'unités';

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'details_supplementaires')]
    private ?string $detailsSupplementaires = null;

    #[ORM\Column(length: 50, nullable: true, name: 'etat')]
    #[Assert\NotBlank(message: 'L\'état du don est obligatoire.')]
    private ?string $etat = 'Neuf / Non ouvert';

    #[ORM\Column(length: 20, name: 'niveau_urgence')]
    #[Assert\NotBlank(message: 'Le niveau d\'urgence est obligatoire.')]
    private ?string $niveauUrgence = 'Moyen';

    #[ORM\Column(length: 20, name: 'statut')]
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true, name: 'date_expiration')]
    #[Assert\GreaterThan(
        value: 'today',
        message: 'La date d\'expiration doit être strictement postérieure à la date d\'aujourd\'hui.'
    )]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(name: 'date_soumission')]
    private ?\DateTimeImmutable $dateSoumission = null;

    // Analyse automatique / scoring (non stocké en base)
    private ?int $scoreAuto = null;
    private ?string $decisionAuto = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTimeImmutable();
        $this->statut = self::STATUT_EN_ATTENTE;
        $this->niveauUrgence = 'Moyen';
    }

    // Getters et Setters
    public function getId(): ?int { return $this->id; }
    public function getCategorie(): ?CategoriesDons { return $this->categorie; }
    public function setCategorie(?CategoriesDons $categorie): static { $this->categorie = $categorie; return $this; }
    public function getArticleDescription(): ?string { return $this->articleDescription; }
    public function setArticleDescription(string $articleDescription): static { $this->articleDescription = $articleDescription; return $this; }
    public function getQuantite(): ?int { return $this->quantite; }
    public function setQuantite(int $quantite): static { $this->quantite = $quantite; return $this; }
    public function getUnite(): ?string { return $this->unite; }
    public function setUnite(?string $unite): static { $this->unite = $unite; return $this; }
    public function getDetailsSupplementaires(): ?string { return $this->detailsSupplementaires; }
    public function setDetailsSupplementaires(?string $detailsSupplementaires): static { $this->detailsSupplementaires = $detailsSupplementaires; return $this; }
    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(?string $etat): static { $this->etat = $etat; return $this; }
    public function getNiveauUrgence(): ?string { return $this->niveauUrgence; }
    public function setNiveauUrgence(string $niveauUrgence): static { $this->niveauUrgence = $niveauUrgence; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getDateExpiration(): ?\DateTimeInterface { return $this->dateExpiration; }
    public function setDateExpiration(?\DateTimeInterface $dateExpiration): static { $this->dateExpiration = $dateExpiration; return $this; }
    public function getDateSoumission(): ?\DateTimeImmutable { return $this->dateSoumission; }
    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static { $this->dateSoumission = $dateSoumission; return $this; }

    public function getScoreAuto(): ?int { return $this->scoreAuto; }
    public function setScoreAuto(?int $scoreAuto): static { $this->scoreAuto = $scoreAuto; return $this; }

    public function getDecisionAuto(): ?string { return $this->decisionAuto; }
    public function setDecisionAuto(?string $decisionAuto): static { $this->decisionAuto = $decisionAuto; return $this; }

    // Méthodes utilitaires
    public function getStatutLabel(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_REJETE => 'Rejeté',
            self::STATUT_ANNULE => 'Annulé',
            default => $this->statut
        };
    }

    public function getCouleurStatut(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_VALIDE => 'success',
            self::STATUT_REJETE => 'danger',
            self::STATUT_ANNULE => 'secondary',
            default => 'secondary'
        };
    }

    public function estEnAttente(): bool { return $this->statut === self::STATUT_EN_ATTENTE; }
    public function estValide(): bool { return $this->statut === self::STATUT_VALIDE; }
    public function estRejete(): bool { return $this->statut === self::STATUT_REJETE; }
    public function estAnnule(): bool { return $this->statut === self::STATUT_ANNULE; }
}