<?php

namespace App\Entity;

use App\Repository\PuzzleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PuzzleRepository::class)]
class Puzzle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 14)]
    #[Assert\Length(exactly: 14)]
    #[Assert\Regex('/^[A-Z]+$/')]
    private ?string $puzzleString = null;

    #[ORM\Column(length: 14)]
    private ?string $remainingLetters = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'puzzle', targetEntity: Submission::class, orphanRemoval: true)]
    private Collection $submissions;

    #[ORM\OneToOne(mappedBy: 'puzzle', targetEntity: Student::class)]
    private ?Student $student = null;

    public function __construct()
    {
        $this->submissions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPuzzleString(): ?string
    {
        return $this->puzzleString;
    }

    public function setPuzzleString(string $puzzleString): static
    {
        $this->puzzleString = strtoupper($puzzleString);
        $this->remainingLetters = $this->puzzleString;
        return $this;
    }

    public function getRemainingLetters(): ?string
    {
        return $this->remainingLetters;
    }

    public function setRemainingLetters(string $remainingLetters): static
    {
        $this->remainingLetters = $remainingLetters;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function addSubmission(Submission $submission): static
    {
        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
            $submission->setPuzzle($this);
        }

        return $this;
    }

    public function removeSubmission(Submission $submission): static
    {
        if ($this->submissions->removeElement($submission)) {
            // set the owning side to null (unless already changed)
            if ($submission->getPuzzle() === $this) {
                $submission->setPuzzle(null);
            }
        }

        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        // unset the owning side of the relation if necessary
        if ($student === null && $this->student !== null) {
            $this->student->setPuzzle(null);
        }

        // set the owning side of the relation if necessary
        if ($student !== null && $student->getPuzzle() !== $this) {
            $student->setPuzzle($this);
        }

        $this->student = $student;

        return $this;
    }

    public function getTotalScore(): int
    {
        $totalScore = 0;
        foreach ($this->submissions as $submission) {
            $totalScore += $submission->getScore();
        }
        return $totalScore;
    }

    public function canUseLetters(string $word): bool
    {
        $word = strtoupper($word);
        $remaining = $this->remainingLetters;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            $pos = strpos($remaining, $letter);
            
            if ($pos === false) {
                return false;
            }
            
            // Remove the used letter from remaining
            $remaining = substr($remaining, 0, $pos) . substr($remaining, $pos + 1);
        }
        
        return true;
    }

    public function useLetters(string $word): void
    {
        $word = strtoupper($word);
        $remaining = $this->remainingLetters;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            $pos = strpos($remaining, $letter);
            
            if ($pos !== false) {
                $remaining = substr($remaining, 0, $pos) . substr($remaining, $pos + 1);
            }
        }
        
        $this->remainingLetters = $remaining;
    }
}
