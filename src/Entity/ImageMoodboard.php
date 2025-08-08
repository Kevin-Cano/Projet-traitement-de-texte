<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'image_moodboard')]
#[Vich\Uploadable]
class ImageMoodboard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nomFichier = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $taille = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mimeType = null;

    #[Vich\UploadableField(mapping: 'moodboard_images', fileNameProperty: 'nomFichier', size: 'taille', mimeType: 'mimeType', originalName: 'nom')]
    private ?File $fichierImage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToOne(targetEntity: Moodboard::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Moodboard $moodboard = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $position = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNomFichier(): ?string
    {
        return $this->nomFichier;
    }

    public function setNomFichier(?string $nomFichier): static
    {
        $this->nomFichier = $nomFichier;
        return $this;
    }

    public function getTaille(): ?int
    {
        return $this->taille;
    }

    public function setTaille(?int $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getFichierImage(): ?File
    {
        return $this->fichierImage;
    }

    public function setFichierImage(?File $fichierImage = null): static
    {
        $this->fichierImage = $fichierImage;

        if (null !== $fichierImage) {
            $this->dateModification = new \DateTimeImmutable();
        }

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

    public function getMoodboard(): ?Moodboard
    {
        return $this->moodboard;
    }

    public function setMoodboard(?Moodboard $moodboard): static
    {
        $this->moodboard = $moodboard;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }
} 