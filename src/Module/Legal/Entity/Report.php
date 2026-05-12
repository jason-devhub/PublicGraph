<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reports')]
class Report
{
    public const REASON_FACTUALLY_INCORRECT = 'factually_incorrect';

    public const REASON_DEFAMATORY = 'defamatory';

    public const REASON_PRIVACY = 'privacy_violation';

    public const REASON_COPYRIGHT = 'copyright';

    public const REASON_OTHER = 'other';

    public const ENTITY_PERSON = 'person';

    public const ENTITY_ORGANIZATION = 'organization';

    public const ENTITY_REVOLVING_DOOR = 'revolving_door';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $entityType = '';

    #[ORM\Column]
    private int $entityId = 0;

    #[ORM\Column(length: 30)]
    private string $reason = self::REASON_OTHER;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_RECEIVED;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
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

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    /**
     * @return list<string>
     */
    public static function allowedEntityTypes(): array
    {
        return [
            self::ENTITY_PERSON,
            self::ENTITY_ORGANIZATION,
            self::ENTITY_REVOLVING_DOOR,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedReasons(): array
    {
        return [
            self::REASON_FACTUALLY_INCORRECT,
            self::REASON_DEFAMATORY,
            self::REASON_PRIVACY,
            self::REASON_COPYRIGHT,
            self::REASON_OTHER,
        ];
    }
}
