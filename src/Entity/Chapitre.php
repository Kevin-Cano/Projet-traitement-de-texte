<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'chapitre')]
class Chapitre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: 'integer')]
    private int $ordre = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToOne(targetEntity: Livre::class, inversedBy: 'chapitres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nombreMots = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        $this->dateModification = new \DateTimeImmutable();
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;
        $this->dateModification = new \DateTimeImmutable();
        
        // Calculer automatiquement le nombre de mots
        if ($contenu) {
            $this->nombreMots = str_word_count(strip_tags($contenu));
        } else {
            $this->nombreMots = 0;
        }
        
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
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

    public function getNombreMots(): ?int
    {
        return $this->nombreMots;
    }

    public function setNombreMots(?int $nombreMots): static
    {
        $this->nombreMots = $nombreMots;
        return $this;
    }
} 