<?php

declare(strict_types=1);

namespace App\Module\Legislation\Entity;

use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Repository\RevolvingDoorRepository;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use App\Shared\Validator\MinSources;
use App\Shared\Validator\RevolvingDoorFactualNote;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MinSources(count: 1)]
#[ORM\Entity(repositoryClass: RevolvingDoorRepository::class)]
#[ORM\Table(name: 'revolving_doors')]
#[ORM\HasLifecycleCallbacks]
class RevolvingDoor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'revolvingDoors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Position::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $sourcePosition = null;

    #[ORM\ManyToOne(targetEntity: Position::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $targetPosition = null;

    #[ORM\ManyToOne(targetEntity: LegislativeAction::class)]
    private ?LegislativeAction $linkingAction = null;

    #[RevolvingDoorFactualNote]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $factualNoteFr = null;

    #[RevolvingDoorFactualNote]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $factualNoteEn = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $delayDays = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getSourcePosition(): ?Position
    {
        return $this->sourcePosition;
    }

    public function setSourcePosition(?Position $sourcePosition): self
    {
        $this->sourcePosition = $sourcePosition;

        return $this;
    }

    public function getTargetPosition(): ?Position
    {
        return $this->targetPosition;
    }

    public function setTargetPosition(?Position $targetPosition): self
    {
        $this->targetPosition = $targetPosition;

        return $this;
    }

    public function getLinkingAction(): ?LegislativeAction
    {
        return $this->linkingAction;
    }

    public function setLinkingAction(?LegislativeAction $linkingAction): self
    {
        $this->linkingAction = $linkingAction;

        return $this;
    }

    public function getFactualNoteFr(): ?string
    {
        return $this->factualNoteFr;
    }

    public function setFactualNoteFr(?string $factualNoteFr): self
    {
        $this->factualNoteFr = $factualNoteFr;

        return $this;
    }

    public function getFactualNoteEn(): ?string
    {
        return $this->factualNoteEn;
    }

    public function setFactualNoteEn(?string $factualNoteEn): self
    {
        $this->factualNoteEn = $factualNoteEn;

        return $this;
    }

    public function getDelayDays(): ?int
    {
        return $this->delayDays;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshDelayDays(): void
    {
        $source = $this->sourcePosition;
        $target = $this->targetPosition;
        if (null === $source || null === $target) {
            $this->delayDays = null;

            return;
        }

        $end = $source->getEndDate();
        $start = $target->getStartDate();
        if (null === $end) {
            $this->delayDays = null;

            return;
        }

        $this->delayDays = (int) floor(($start->getTimestamp() - $end->getTimestamp()) / 86400);
    }
}
