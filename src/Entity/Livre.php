<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'livre')]
class Livre
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
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Chapitre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $chapitres;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Personnage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $personnages;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Moodboard::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $moodboards;

    public function __construct()
    {
        $this->chapitres = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->moodboards = new ArrayCollection();
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

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;
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

    /**
     * @return Collection<int, Chapitre>
     */
    public function getChapitres(): Collection
    {
        return $this->chapitres;
    }

    public function addChapitre(Chapitre $chapitre): static
    {
        if (!$this->chapitres->contains($chapitre)) {
            $this->chapitres->add($chapitre);
            $chapitre->setLivre($this);
        }

        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            if ($chapitre->getLivre() === $this) {
                $chapitre->setLivre(null);
            }
        }

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
            $personnage->setLivre($this);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        if ($this->personnages->removeElement($personnage)) {
            if ($personnage->getLivre() === $this) {
                $personnage->setLivre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Moodboard>
     */
    public function getMoodboards(): Collection
    {
        return $this->moodboards;
    }

    public function addMoodboard(Moodboard $moodboard): static
    {
        if (!$this->moodboards->contains($moodboard)) {
            $this->moodboards->add($moodboard);
            $moodboard->setLivre($this);
        }

        return $this;
    }

    public function removeMoodboard(Moodboard $moodboard): static
    {
        if ($this->moodboards->removeElement($moodboard)) {
            if ($moodboard->getLivre() === $this) {
                $moodboard->setLivre(null);
            }
        }

        return $this;
    }
} 