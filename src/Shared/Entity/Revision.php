<?php

declare(strict_types=1);

namespace App\Shared\Entity;

use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'revisions')]
class Revision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $entityType;

    #[ORM\Column]
    private int $entityId;

    #[ORM\Column(length: 100)]
    private string $fieldChanged;

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $oldValue;

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $newValue;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'revisionsProposed')]
    #[ORM\JoinColumn(nullable: false)]
    private User $proposedBy;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'revisionsValidated')]
    #[ORM\JoinColumn(nullable: false)]
    private User $validatedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $entityType,
        int $entityId,
        string $fieldChanged,
        mixed $oldValue,
        mixed $newValue,
        User $proposedBy,
        User $validatedBy,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->fieldChanged = $fieldChanged;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->proposedBy = $proposedBy;
        $this->validatedBy = $validatedBy;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getFieldChanged(): string
    {
        return $this->fieldChanged;
    }

    public function getOldValue(): mixed
    {
        return $this->oldValue;
    }

    public function getNewValue(): mixed
    {
        return $this->newValue;
    }

    public function getProposedBy(): User
    {
        return $this->proposedBy;
    }

    public function getValidatedBy(): User
    {
        return $this->validatedBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
