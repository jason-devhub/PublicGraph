<?php

declare(strict_types=1);

namespace App\Module\Legal\Entity;

use App\Module\Person\Entity\Person;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'right_of_reply_requests')]
class RightOfReplyRequest
{
    public const TYPE_RECTIFICATION = 'rectification';

    public const TYPE_REMOVAL = 'removal';

    public const TYPE_ADDITION = 'addition';

    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'rightOfReplyRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\Column(length: 200)]
    private string $requesterName = '';

    #[ORM\Column(length: 200)]
    private string $requesterQuality = '';

    #[ORM\Column(length: 180)]
    private string $requesterEmail = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $requesterPhone = null;

    #[ORM\Column(length: 500)]
    private string $identityPdfPath = '';

    #[ORM\Column(length: 30)]
    private string $requestType = self::TYPE_OTHER;

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

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

    public function getRequesterName(): string
    {
        return $this->requesterName;
    }

    public function setRequesterName(string $requesterName): self
    {
        $this->requesterName = $requesterName;

        return $this;
    }

    public function getRequesterQuality(): string
    {
        return $this->requesterQuality;
    }

    public function setRequesterQuality(string $requesterQuality): self
    {
        $this->requesterQuality = $requesterQuality;

        return $this;
    }

    public function getRequesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function setRequesterEmail(string $requesterEmail): self
    {
        $this->requesterEmail = $requesterEmail;

        return $this;
    }

    public function getRequesterPhone(): ?string
    {
        return $this->requesterPhone;
    }

    public function setRequesterPhone(?string $requesterPhone): self
    {
        $this->requesterPhone = $requesterPhone;

        return $this;
    }

    public function getIdentityPdfPath(): string
    {
        return $this->identityPdfPath;
    }

    public function setIdentityPdfPath(string $identityPdfPath): self
    {
        $this->identityPdfPath = $identityPdfPath;

        return $this;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function setRequestType(string $requestType): self
    {
        $this->requestType = $requestType;

        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
