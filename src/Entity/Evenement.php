<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'événement ne peut pas être vide')]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $type = null; // bataille, naissance, mort, découverte, etc.

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $dateEvenement = null; // Date dans l'univers fictif

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $anneeEvenement = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $importance = null; // mineur, majeur, critique

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $causes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $consequences = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $protagonistes = null; // IDs des personnages impliqués

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $temoin = null; // Témoins de l'événement

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $secret = false; // Événement secret/caché

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToOne(targetEntity: Livre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\ManyToOne(targetEntity: Lieu::class, inversedBy: 'evenements')]
    private ?Lieu $lieu = null;

    #[ORM\ManyToMany(targetEntity: Personnage::class)]
    #[ORM\JoinTable(name: 'evenement_personnage')]
    private Collection $personnages;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'evenementsSuivants')]
    private ?Evenement $evenementPrecedent = null;

    #[ORM\OneToMany(mappedBy: 'evenementPrecedent', targetEntity: Evenement::class)]
    private Collection $evenementsSuivants;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->personnages = new ArrayCollection();
        $this->evenementsSuivants = new ArrayCollection();
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
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDateEvenement(): ?string
    {
        return $this->dateEvenement;
    }

    public function setDateEvenement(?string $dateEvenement): static
    {
        $this->dateEvenement = $dateEvenement;
        return $this;
    }

    public function getAnneeEvenement(): ?int
    {
        return $this->anneeEvenement;
    }

    public function setAnneeEvenement(?int $anneeEvenement): static
    {
        $this->anneeEvenement = $anneeEvenement;
        return $this;
    }

    public function getImportance(): ?string
    {
        return $this->importance;
    }

    public function setImportance(?string $importance): static
    {
        $this->importance = $importance;
        return $this;
    }

    public function getCauses(): ?string
    {
        return $this->causes;
    }

    public function setCauses(?string $causes): static
    {
        $this->causes = $causes;
        return $this;
    }

    public function getConsequences(): ?string
    {
        return $this->consequences;
    }

    public function setConsequences(?string $consequences): static
    {
        $this->consequences = $consequences;
        return $this;
    }

    public function getProtagonistes(): ?array
    {
        return $this->protagonistes;
    }

    public function setProtagonistes(?array $protagonistes): static
    {
        $this->protagonistes = $protagonistes;
        return $this;
    }

    public function getTemoin(): ?array
    {
        return $this->temoin;
    }

    public function setTemoin(?array $temoin): static
    {
        $this->temoin = $temoin;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function isSecret(): bool
    {
        return $this->secret;
    }

    public function setSecret(bool $secret): static
    {
        $this->secret = $secret;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateModification(): ?\DateTimeImmutable
    {
        return $this->dateModification;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): static
    {
        $this->livre = $livre;
        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        $this->personnages->removeElement($personnage);

        return $this;
    }

    public function getEvenementPrecedent(): ?Evenement
    {
        return $this->evenementPrecedent;
    }

    public function setEvenementPrecedent(?Evenement $evenementPrecedent): static
    {
        $this->evenementPrecedent = $evenementPrecedent;
        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsSuivants(): Collection
    {
        return $this->evenementsSuivants;
    }

    public function addEvenementSuivant(Evenement $evenementSuivant): static
    {
        if (!$this->evenementsSuivants->contains($evenementSuivant)) {
            $this->evenementsSuivants->add($evenementSuivant);
            $evenementSuivant->setEvenementPrecedent($this);
        }

        return $this;
    }

    public function removeEvenementSuivant(Evenement $evenementSuivant): static
    {
        if ($this->evenementsSuivants->removeElement($evenementSuivant)) {
            if ($evenementSuivant->getEvenementPrecedent() === $this) {
                $evenementSuivant->setEvenementPrecedent(null);
            }
        }

        return $this;
    }

    /**
     * Retourne l'événement sous forme de tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'type' => $this->type,
            'dateEvenement' => $this->dateEvenement,
            'anneeEvenement' => $this->anneeEvenement,
            'importance' => $this->importance,
            'causes' => $this->causes,
            'consequences' => $this->consequences,
            'protagonistes' => $this->protagonistes,
            'temoin' => $this->temoin,
            'notes' => $this->notes,
            'secret' => $this->secret,
            'lieu' => $this->lieu ? $this->lieu->getNom() : null,
            'nb_personnages' => $this->personnages->count(),
            'nb_evenements_suivants' => $this->evenementsSuivants->count()
        ];
    }

    /**
     * Retourne un résumé court de l'événement
     */
    public function getResume(): string
    {
        $resume = $this->nom;
        
        if ($this->dateEvenement) {
            $resume .= " ({$this->dateEvenement})";
        } elseif ($this->anneeEvenement) {
            $resume .= " ({$this->anneeEvenement})";
        }
        
        if ($this->lieu) {
            $resume .= " à " . $this->lieu->getNom();
        }
        
        return $resume;
    }
} 