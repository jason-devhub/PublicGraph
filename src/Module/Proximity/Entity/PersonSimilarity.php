<?php

declare(strict_types=1);

namespace App\Module\Proximity\Entity;

use App\Module\Person\Entity\Person;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: PersonSimilarityRepository::class)]
#[ORM\Table(name: 'person_similarities')]
#[ORM\Index(name: 'idx_similarity_a_score', columns: ['person_a_id', 'score'])]
#[ORM\UniqueConstraint(name: 'uniq_similarity_pair', columns: ['person_a_id', 'person_b_id'])]
class PersonSimilarity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $personA = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $personB = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $score = '0.00';

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $details = [];

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $computedAt;

    public function __construct()
    {
        $this->computedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonA(): ?Person
    {
        return $this->personA;
    }

    public function setPersonA(?Person $personA): self
    {
        $this->personA = $personA;

        return $this;
    }

    public function getPersonB(): ?Person
    {
        return $this->personB;
    }

    public function setPersonB(?Person $personB): self
    {
        $this->personB = $personB;

        return $this;
    }

    public function getScore(): string
    {
        return $this->score;
    }

    public function setScore(string $score): self
    {
        $this->score = $score;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getDetails(): array
    {
        return $this->details;
    }

    /** @param array<string, mixed> $details */
    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getComputedAt(): \DateTimeImmutable
    {
        return $this->computedAt;
    }
}
