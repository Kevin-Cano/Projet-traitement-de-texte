<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'lieu')]
class Lieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du lieu ne peut pas être vide')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $type = null; // ville, forêt, château, etc.

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $histoire = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $coordonnees = null; // {x, y} sur une carte

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $climat = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $population = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $gouvernement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $economie = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $culture = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $langues = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $religions = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $ressources = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $dangers = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $architecture = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $points_interet = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $galerie = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateModification = null;

    #[ORM\ManyToOne(targetEntity: Livre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\ManyToOne(targetEntity: Lieu::class, inversedBy: 'sousLieux')]
    private ?Lieu $lieuParent = null;

    #[ORM\OneToMany(mappedBy: 'lieuParent', targetEntity: Lieu::class)]
    private Collection $sousLieux;

    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'lieux')]
    private Collection $personnages;

    #[ORM\OneToMany(mappedBy: 'lieu', targetEntity: Evenement::class)]
    private Collection $evenements;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
        $this->sousLieux = new ArrayCollection();
        $this->personnages = new ArrayCollection();
        $this->evenements = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
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

    public function getCoordonnees(): ?array
    {
        return $this->coordonnees;
    }

    public function setCoordonnees(?array $coordonnees): static
    {
        $this->coordonnees = $coordonnees;
        return $this;
    }

    public function getClimat(): ?string
    {
        return $this->climat;
    }

    public function setClimat(?string $climat): static
    {
        $this->climat = $climat;
        return $this;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function setPopulation(?int $population): static
    {
        $this->population = $population;
        return $this;
    }

    public function getGouvernement(): ?string
    {
        return $this->gouvernement;
    }

    public function setGouvernement(?string $gouvernement): static
    {
        $this->gouvernement = $gouvernement;
        return $this;
    }

    public function getEconomie(): ?string
    {
        return $this->economie;
    }

    public function setEconomie(?string $economie): static
    {
        $this->economie = $economie;
        return $this;
    }

    public function getCulture(): ?string
    {
        return $this->culture;
    }

    public function setCulture(?string $culture): static
    {
        $this->culture = $culture;
        return $this;
    }

    public function getLangues(): ?array
    {
        return $this->langues;
    }

    public function setLangues(?array $langues): static
    {
        $this->langues = $langues;
        return $this;
    }

    public function getReligions(): ?array
    {
        return $this->religions;
    }

    public function setReligions(?array $religions): static
    {
        $this->religions = $religions;
        return $this;
    }

    public function getRessources(): ?array
    {
        return $this->ressources;
    }

    public function setRessources(?array $ressources): static
    {
        $this->ressources = $ressources;
        return $this;
    }

    public function getDangers(): ?array
    {
        return $this->dangers;
    }

    public function setDangers(?array $dangers): static
    {
        $this->dangers = $dangers;
        return $this;
    }

    public function getArchitecture(): ?string
    {
        return $this->architecture;
    }

    public function setArchitecture(?string $architecture): static
    {
        $this->architecture = $architecture;
        return $this;
    }

    public function getPointsInteret(): ?array
    {
        return $this->points_interet;
    }

    public function setPointsInteret(?array $points_interet): static
    {
        $this->points_interet = $points_interet;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getGalerie(): ?array
    {
        return $this->galerie;
    }

    public function setGalerie(?array $galerie): static
    {
        $this->galerie = $galerie;
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

    public function getLieuParent(): ?Lieu
    {
        return $this->lieuParent;
    }

    public function setLieuParent(?Lieu $lieuParent): static
    {
        $this->lieuParent = $lieuParent;
        return $this;
    }

    /**
     * @return Collection<int, Lieu>
     */
    public function getSousLieux(): Collection
    {
        return $this->sousLieux;
    }

    public function addSousLieu(Lieu $sousLieu): static
    {
        if (!$this->sousLieux->contains($sousLieu)) {
            $this->sousLieux->add($sousLieu);
            $sousLieu->setLieuParent($this);
        }

        return $this;
    }

    public function removeSousLieu(Lieu $sousLieu): static
    {
        if ($this->sousLieux->removeElement($sousLieu)) {
            if ($sousLieu->getLieuParent() === $this) {
                $sousLieu->setLieuParent(null);
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
            $personnage->addLieu($this);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        if ($this->personnages->removeElement($personnage)) {
            $personnage->removeLieu($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setLieu($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            if ($evenement->getLieu() === $this) {
                $evenement->setLieu(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le chemin complet du lieu (parent > sous-lieu)
     */
    public function getCheminComplet(): string
    {
        $chemin = [$this->nom];
        $parent = $this->lieuParent;
        
        while ($parent) {
            array_unshift($chemin, $parent->getNom());
            $parent = $parent->getLieuParent();
        }
        
        return implode(' > ', $chemin);
    }

    /**
     * Retourne toutes les données du lieu sous forme de tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type' => $this->type,
            'description' => $this->description,
            'histoire' => $this->histoire,
            'coordonnees' => $this->coordonnees,
            'climat' => $this->climat,
            'population' => $this->population,
            'gouvernement' => $this->gouvernement,
            'economie' => $this->economie,
            'culture' => $this->culture,
            'langues' => $this->langues,
            'religions' => $this->religions,
            'ressources' => $this->ressources,
            'dangers' => $this->dangers,
            'architecture' => $this->architecture,
            'points_interet' => $this->points_interet,
            'chemin_complet' => $this->getCheminComplet(),
            'nb_personnages' => $this->personnages->count(),
            'nb_evenements' => $this->evenements->count(),
            'nb_sous_lieux' => $this->sousLieux->count()
        ];
    }
} 