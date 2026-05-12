<?php

declare(strict_types=1);

namespace App\Module\Organization\Entity;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'organizations')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_org_type', columns: ['type'])]
#[ORM\Index(name: 'idx_org_wikidata', columns: ['wikidata_id'])]
class Organization
{
    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_INFLUENCE_NETWORK = 'influence_network';

    public const TYPE_POLITICAL_PARTY = 'political_party';

    public const TYPE_CORPORATION = 'corporation';

    public const TYPE_MEDIA_GROUP = 'media_group';

    public const TYPE_GOVERNMENT_BODY = 'government_body';

    public const TYPE_INTERNATIONAL_BODY = 'international_body';

    public const TYPE_THINK_TANK = 'think_tank';

    public const TYPE_LOBBY_GROUP = 'lobby_group';

    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    #[Gedmo\Slug(fields: ['officialName'], updatable: false)]
    #[ORM\Column(length: 250, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 250)]
    private string $officialName = '';

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_OTHER;

    /** @var Collection<int, Country> */
    #[ORM\ManyToMany(targetEntity: Country::class, inversedBy: 'organizations')]
    #[ORM\JoinTable(name: 'organization_country')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'country_iso_code', referencedColumnName: 'iso_code', onDelete: 'CASCADE')]
    private Collection $countries;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $foundedYear = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $dissolvedYear = null;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    private ?string $wikidataId = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    /** @var Collection<int, OrganizationTranslation> */
    #[ORM\OneToMany(targetEntity: OrganizationTranslation::class, mappedBy: 'organization', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $translations;

    #[ORM\OneToOne(targetEntity: Party::class, mappedBy: 'organization', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Party $partyDetails = null;

    /** @var Collection<int, Membership> */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'organization')]
    private Collection $memberships;

    /** @var Collection<int, Position> */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'organization')]
    private Collection $positions;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->countries = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->positions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function __toString(): string
    {
        return $this->officialName;
    }

    public static function isAllowedStatusTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            self::STATUS_PENDING => \in_array($to, [self::STATUS_APPROVED, self::STATUS_REJECTED], true),
            self::STATUS_APPROVED => self::STATUS_ARCHIVED === $to,
            self::STATUS_REJECTED => self::STATUS_PENDING === $to,
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

    public function getOfficialName(): string
    {
        return $this->officialName;
    }

    public function setOfficialName(string $officialName): self
    {
        $this->officialName = $officialName;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /** @return Collection<int, Country> */
    public function getCountries(): Collection
    {
        return $this->countries;
    }

    public function addCountry(Country $country): self
    {
        if (!$this->countries->contains($country)) {
            $this->countries->add($country);
        }

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getFoundedYear(): ?int
    {
        return $this->foundedYear;
    }

    public function setFoundedYear(?int $foundedYear): self
    {
        $this->foundedYear = $foundedYear;

        return $this;
    }

    public function getDissolvedYear(): ?int
    {
        return $this->dissolvedYear;
    }

    public function setDissolvedYear(?int $dissolvedYear): self
    {
        $this->dissolvedYear = $dissolvedYear;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /** @return Collection<int, OrganizationTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(OrganizationTranslation $t): self
    {
        if (!$this->translations->contains($t)) {
            $this->translations->add($t);
            $t->setOrganization($this);
        }

        return $this;
    }

    public function getPartyDetails(): ?Party
    {
        return $this->partyDetails;
    }

    public function setPartyDetails(?Party $party): self
    {
        $this->partyDetails = $party;
        if (null !== $party) {
            $party->setOrganization($this);
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function prePersistUuid(): void
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::v7();
        }
    }
}
