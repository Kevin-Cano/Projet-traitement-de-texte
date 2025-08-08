<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'writing_session')]
class WritingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chapitre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chapitre $chapitre = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(type: 'integer')]
    private int $initialWordCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $finalWordCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $wordsAdded = 0;

    #[ORM\Column(type: 'integer')]
    private int $wordsDeleted = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $wordsPerMinute = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $keystrokes = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pauseCount = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $longestPauseMinutes = null;

    #[ORM\Column(type: 'boolean')]
    private bool $goalReached = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dailyGoal = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $mood = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $energyLevel = null; // 1-5

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $distractions = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $textAnalysis = null;

    public function __construct()
    {
        $this->startTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChapitre(): ?Chapitre
    {
        return $this->chapitre;
    }

    public function setChapitre(?Chapitre $chapitre): static
    {
        $this->chapitre = $chapitre;
        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;
        
        // Calculer la durée automatiquement
        if ($endTime && $this->startTime) {
            $duration = $endTime->getTimestamp() - $this->startTime->getTimestamp();
            $this->durationMinutes = (int) ceil($duration / 60);
            
            // Calculer les mots par minute
            if ($this->durationMinutes > 0 && $this->wordsAdded > 0) {
                $this->wordsPerMinute = round($this->wordsAdded / $this->durationMinutes, 2);
            }
        }
        
        return $this;
    }

    public function getInitialWordCount(): int
    {
        return $this->initialWordCount;
    }

    public function setInitialWordCount(int $initialWordCount): static
    {
        $this->initialWordCount = $initialWordCount;
        return $this;
    }

    public function getFinalWordCount(): int
    {
        return $this->finalWordCount;
    }

    public function setFinalWordCount(int $finalWordCount): static
    {
        $this->finalWordCount = $finalWordCount;
        
        // Calculer les mots ajoutés/supprimés
        $difference = $finalWordCount - $this->initialWordCount;
        if ($difference >= 0) {
            $this->wordsAdded = $difference;
            $this->wordsDeleted = 0;
        } else {
            $this->wordsAdded = 0;
            $this->wordsDeleted = abs($difference);
        }
        
        return $this;
    }

    public function getWordsAdded(): int
    {
        return $this->wordsAdded;
    }

    public function getWordsDeleted(): int
    {
        return $this->wordsDeleted;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function getWordsPerMinute(): ?float
    {
        return $this->wordsPerMinute;
    }

    public function getKeystrokes(): ?array
    {
        return $this->keystrokes;
    }

    public function setKeystrokes(?array $keystrokes): static
    {
        $this->keystrokes = $keystrokes;
        return $this;
    }

    public function getPauseCount(): ?int
    {
        return $this->pauseCount;
    }

    public function setPauseCount(?int $pauseCount): static
    {
        $this->pauseCount = $pauseCount;
        return $this;
    }

    public function getLongestPauseMinutes(): ?int
    {
        return $this->longestPauseMinutes;
    }

    public function setLongestPauseMinutes(?int $longestPauseMinutes): static
    {
        $this->longestPauseMinutes = $longestPauseMinutes;
        return $this;
    }

    public function isGoalReached(): bool
    {
        return $this->goalReached;
    }

    public function setGoalReached(bool $goalReached): static
    {
        $this->goalReached = $goalReached;
        return $this;
    }

    public function getDailyGoal(): ?int
    {
        return $this->dailyGoal;
    }

    public function setDailyGoal(?int $dailyGoal): static
    {
        $this->dailyGoal = $dailyGoal;
        
        // Vérifier si l'objectif est atteint
        if ($dailyGoal && $this->wordsAdded >= $dailyGoal) {
            $this->goalReached = true;
        }
        
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

    public function getMood(): ?string
    {
        return $this->mood;
    }

    public function setMood(?string $mood): static
    {
        $this->mood = $mood;
        return $this;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(?int $energyLevel): static
    {
        $this->energyLevel = $energyLevel;
        return $this;
    }

    public function getDistractions(): ?array
    {
        return $this->distractions;
    }

    public function setDistractions(?array $distractions): static
    {
        $this->distractions = $distractions;
        return $this;
    }

    public function getTextAnalysis(): ?array
    {
        return $this->textAnalysis;
    }

    public function setTextAnalysis(?array $textAnalysis): static
    {
        $this->textAnalysis = $textAnalysis;
        return $this;
    }

    /**
     * Calcule l'efficacité de la session (0-100)
     */
    public function getEfficiency(): float
    {
        if (!$this->durationMinutes || $this->durationMinutes === 0) {
            return 0;
        }

        $efficiency = 100;

        // Pénalité pour les pauses longues
        if ($this->longestPauseMinutes && $this->longestPauseMinutes > 10) {
            $efficiency -= min(30, $this->longestPauseMinutes - 10);
        }

        // Pénalité pour trop de pauses
        if ($this->pauseCount && $this->pauseCount > 5) {
            $efficiency -= min(20, ($this->pauseCount - 5) * 2);
        }

        // Bonus pour atteindre l'objectif
        if ($this->goalReached) {
            $efficiency += 10;
        }

        // Ajustement selon les mots par minute
        if ($this->wordsPerMinute) {
            if ($this->wordsPerMinute > 20) {
                $efficiency += 10; // Très productif
            } elseif ($this->wordsPerMinute < 5) {
                $efficiency -= 20; // Peu productif
            }
        }

        return max(0, min(100, $efficiency));
    }

    /**
     * Retourne un résumé de la session
     */
    public function getSummary(): string
    {
        $parts = [];
        
        if ($this->wordsAdded > 0) {
            $parts[] = "{$this->wordsAdded} mots ajoutés";
        }
        
        if ($this->durationMinutes) {
            $hours = floor($this->durationMinutes / 60);
            $minutes = $this->durationMinutes % 60;
            if ($hours > 0) {
                $parts[] = "{$hours}h{$minutes}min";
            } else {
                $parts[] = "{$minutes}min";
            }
        }
        
        if ($this->wordsPerMinute) {
            $parts[] = "{$this->wordsPerMinute} mots/min";
        }
        
        if ($this->goalReached) {
            $parts[] = "objectif atteint";
        }
        
        return implode(', ', $parts);
    }
} 