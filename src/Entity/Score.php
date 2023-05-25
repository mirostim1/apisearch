<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScoreRepository::class)]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $score = null;

    #[ORM\Column(length: 255)]
    private ?string $term = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(string $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }
}
