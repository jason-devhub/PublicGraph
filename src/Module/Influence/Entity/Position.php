<?php

declare(strict_types=1);

namespace App\Module\Influence\Entity;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Repository\PositionRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use App\Shared\Validator\MinSources;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MinSources(count: 1)]
#[ORM\Entity(repositoryClass: PositionRepository::class)]
#[ORM\Table(name: 'positions')]
#[ORM\Index(name: 'idx_position_nature', columns: ['nature'])]
#[ORM\Index(name: 'idx_position_startdate', columns: ['start_date'])]
class Position
{
    public const NATURE_ELECTED_OFFICE = 'elected_office';

    public const NATURE_APPOINTED_OFFICE = 'appointed_office';

    public const NATURE_CORPORATE_POSITION = 'corporate_position';

    public const NATURE_BOARD_MEMBER = 'board_member';

    public const NATURE_ADVISOR = 'advisor';

    public const NATURE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Organization $organization = null;

    #[ORM\Column(length: 200)]
    private string $titleFr = '';

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(length: 30)]
    private string $nature = self::NATURE_OTHER;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(name: 'country_iso_code', referencedColumnName: 'iso_code', nullable: true, onDelete: 'SET NULL')]
    private ?Country $country = null;

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

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getTitleFr(): string
    {
        return $this->titleFr;
    }

    public function setTitleFr(string $titleFr): self
    {
        $this->titleFr = $titleFr;

        return $this;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function setTitleEn(?string $titleEn): self
    {
        $this->titleEn = $titleEn;

        return $this;
    }

    public function getNature(): string
    {
        return $this->nature;
    }

    public function setNature(string $nature): self
    {
        $this->nature = $nature;

        return $this;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

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
}
