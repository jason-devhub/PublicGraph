<?php

declare(strict_types=1);

namespace App\Module\Person\Entity;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Legal\Entity\RightOfReplyRequest;
use App\Module\Legislation\Entity\LegislativeAction;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Person\Repository\PersonRepository;
use App\Module\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[ORM\Table(name: 'persons')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_person_status', columns: ['status'])]
#[ORM\Index(name: 'idx_person_wikidata', columns: ['wikidata_id'])]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Person
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    #[Gedmo\Slug(fields: ['givenName', 'familyName'], updatable: false)]
    #[ORM\Column(length: 200, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 100)]
    private string $givenName = '';

    #[ORM\Column(length: 100)]
    private string $familyName = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $usageName = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $deathDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gender = null;

    /** @var Collection<int, Country> */
    #[ORM\ManyToMany(targetEntity: Country::class, inversedBy: 'persons')]
    #[ORM\JoinTable(name: 'person_nationality')]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'country_iso_code', referencedColumnName: 'iso_code', onDelete: 'CASCADE')]
    private Collection $nationalities;

    /** @var list<string> */
    #[ORM\Column(type: 'simple_array')]
    private array $roleCategories = [];

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    private ?string $wikidataId = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastWikidataSyncAt = null;

    /** @var list<string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $wikidataManuallyEditedFields = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'personsCreated')]
    private ?User $createdBy = null;

    /** @var Collection<int, PersonTranslation> */
    #[ORM\OneToMany(targetEntity: PersonTranslation::class, mappedBy: 'person', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    /** @var Collection<int, Membership> */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'person')]
    private Collection $memberships;

    /** @var Collection<int, Position> */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'person')]
    private Collection $positions;

    /** @var Collection<int, LegislativeAction> */
    #[ORM\OneToMany(targetEntity: LegislativeAction::class, mappedBy: 'author')]
    private Collection $legislativeActions;

    /** @var Collection<int, RevolvingDoor> */
    #[ORM\OneToMany(targetEntity: RevolvingDoor::class, mappedBy: 'person')]
    private Collection $revolvingDoors;

    /** @var Collection<int, RightOfReplyRequest> */
    #[ORM\OneToMany(targetEntity: RightOfReplyRequest::class, mappedBy: 'person')]
    private Collection $rightOfReplyRequests;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->nationalities = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->positions = new ArrayCollection();
        $this->legislativeActions = new ArrayCollection();
        $this->revolvingDoors = new ArrayCollection();
        $this->rightOfReplyRequests = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function __toString(): string
    {
        return trim($this->givenName.' '.$this->familyName);
    }

    public static function isAllowedStatusTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            self::STATUS_DRAFT => self::STATUS_PENDING === $to,
            self::STATUS_PENDING => \in_array($to, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_DRAFT], true),
            self::STATUS_APPROVED => self::STATUS_ARCHIVED === $to,
            self::STATUS_REJECTED => self::STATUS_DRAFT === $to || self::STATUS_PENDING === $to,
            self::STATUS_ARCHIVED => false,
            default => false,
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getGivenName(): string
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getUsageName(): ?string
    {
        return $this->usageName;
    }

    public function setUsageName(?string $usageName): self
    {
        $this->usageName = $usageName;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getDeathDate(): ?\DateTimeImmutable
    {
        return $this->deathDate;
    }

    public function setDeathDate(?\DateTimeImmutable $deathDate): self
    {
        $this->deathDate = $deathDate;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    /** @return Collection<int, Country> */
    public function getNationalities(): Collection
    {
        return $this->nationalities;
    }

    public function addNationality(Country $country): self
    {
        if (!$this->nationalities->contains($country)) {
            $this->nationalities->add($country);
        }

        return $this;
    }

    /** @return list<string> */
    public function getRoleCategories(): array
    {
        return $this->roleCategories;
    }

    /** @param list<string> $roleCategories */
    public function setRoleCategories(array $roleCategories): self
    {
        $this->roleCategories = $roleCategories;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): self
    {
        $this->photoUrl = $photoUrl;

        return $this;
    }

    public function getWikidataId(): ?string
    {
        return $this->wikidataId;
    }

    public function setWikidataId(?string $wikidataId): self
    {
        $this->wikidataId = $wikidataId;

        return $this;
    }

    public function getLastWikidataSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastWikidataSyncAt;
    }

    public function setLastWikidataSyncAt(?\DateTimeImmutable $lastWikidataSyncAt): self
    {
        $this->lastWikidataSyncAt = $lastWikidataSyncAt;

        return $this;
    }

    /** @return list<string> */
    public function getWikidataManuallyEditedFields(): array
    {
        return $this->wikidataManuallyEditedFields ?? [];
    }

    /** @param list<string> $fields */
    public function setWikidataManuallyEditedFields(array $fields): self
    {
        $this->wikidataManuallyEditedFields = [] === $fields ? null : array_values(array_unique($fields));

        return $this;
    }

    public function isFieldManuallyEditedForWikidata(string $field): bool
    {
        return \in_array($field, $this->getWikidataManuallyEditedFields(), true);
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

    /** @return Collection<int, PersonTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(PersonTranslation $t): self
    {
        if (!$this->translations->contains($t)) {
            $this->translations->add($t);
            $t->setPerson($this);
        }

        return $this;
    }

    /** @return Collection<int, Membership> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /** @return Collection<int, Position> */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    /** @return Collection<int, LegislativeAction> */
    public function getLegislativeActions(): Collection
    {
        return $this->legislativeActions;
    }

    /** @return Collection<int, RevolvingDoor> */
    public function getRevolvingDoors(): Collection
    {
        return $this->revolvingDoors;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersistUuid(): void
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::v7();
        }
    }
}
