<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'personnage')]
class Personnage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $age = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apparencePhysique = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $personnalite = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $histoire = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $relations = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToMany(targetEntity: Lieu::class, inversedBy: 'personnages')]
    #[ORM\JoinTable(name: 'personnage_lieu')]
    private Collection $lieux;

    #[ORM\ManyToOne(targetEntity: Livre::class, inversedBy: 'personnages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->lieux = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;
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

    public function getApparencePhysique(): ?string
    {
        return $this->apparencePhysique;
    }

    public function setApparencePhysique(?string $apparencePhysique): static
    {
        $this->apparencePhysique = $apparencePhysique;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getPersonnalite(): ?string
    {
        return $this->personnalite;
    }

    public function setPersonnalite(?string $personnalite): static
    {
        $this->personnalite = $personnalite;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getHistoire(): ?string
    {
        return $this->histoire;
    }

    public function setHistoire(?string $histoire): static
    {
        $this->histoire = $histoire;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getRelations(): ?string
    {
        return $this->relations;
    }

    public function setRelations(?string $relations): static
    {
        $this->relations = $relations;
        $this->dateModification = new \DateTimeImmutable();
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

    /**
     * @return Collection<int, Lieu>
     */
    public function getLieux(): Collection
    {
        return $this->lieux;
    }

    public function addLieu(Lieu $lieu): static
    {
        if (!$this->lieux->contains($lieu)) {
            $this->lieux->add($lieu);
        }

        return $this;
    }

    public function removeLieu(Lieu $lieu): static
    {
        $this->lieux->removeElement($lieu);

        return $this;
    }

    public function getNomComplet(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }
} 