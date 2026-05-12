<?php

declare(strict_types=1);

namespace App\Module\Moderation\Entity;

use App\Module\Moderation\Repository\ChangeProposalRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ChangeProposalRepository::class)]
#[ORM\Table(name: 'change_proposals')]
class ChangeProposal
{
    public const ENTITY_PERSON = 'person';

    public const ENTITY_ORGANIZATION = 'organization';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $entityType = '';

    #[ORM\Column]
    private int $entityId = 0;

    /** @var array<string, array{old: mixed, new: mixed}> */
    #[ORM\Column(type: 'json')]
    private array $diff = [];

    #[ORM\Column(type: 'text')]
    private string $justification = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'changeProposalsSubmitted')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $submittedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'changeProposalsModerated')]
    private ?User $moderatedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

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

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    /** @return array<string, array{old: mixed, new: mixed}> */
    public function getDiff(): array
    {
        return $this->diff;
    }

    /** @param array<string, array{old: mixed, new: mixed}> $diff */
    public function setDiff(array $diff): self
    {
        $this->diff = $diff;

        return $this;
    }

    public function getJustification(): string
    {
        return $this->justification;
    }

    public function setJustification(string $justification): self
    {
        $this->justification = $justification;

        return $this;
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

    public function getSubmittedBy(): ?User
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(?User $submittedBy): self
    {
        $this->submittedBy = $submittedBy;

        return $this;
    }

    public function getModeratedBy(): ?User
    {
        return $this->moderatedBy;
    }

    public function setModeratedBy(?User $moderatedBy): self
    {
        $this->moderatedBy = $moderatedBy;

        return $this;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeImmutable $moderatedAt): self
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;

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
}
