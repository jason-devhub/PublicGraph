<?php

declare(strict_types=1);

namespace App\Module\Source\Entity;

use App\Module\Source\Repository\EntitySourceRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: EntitySourceRepository::class)]
#[ORM\Table(name: 'entity_sources')]
#[ORM\Index(name: 'idx_entity_source_target', columns: ['entity_type', 'entity_id'])]
class EntitySource
{
    public const ENTITY_MEMBERSHIP = 'membership';

    public const ENTITY_POSITION = 'position';

    public const ENTITY_LEGISLATIVE_ACTION = 'legislative_action';

    public const ENTITY_REVOLVING_DOOR = 'revolving_door';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Source::class, inversedBy: 'entitySources')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Source $source = null;

    #[ORM\Column(length: 30)]
    private string $entityType = '';

    #[ORM\Column]
    private int $entityId = 0;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'entitySourcesAdded')]
    private ?User $addedBy = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource(?Source $source): self
    {
        $this->source = $source;

        return $this;
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

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(?User $addedBy): self
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
