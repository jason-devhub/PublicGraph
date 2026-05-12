<?php

declare(strict_types=1);

namespace App\Module\Legislation\Entity;

use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Repository\LegislativeActionRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use App\Shared\Validator\MinSources;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MinSources(count: 1)]
#[ORM\Entity(repositoryClass: LegislativeActionRepository::class)]
#[ORM\Table(name: 'legislative_actions')]
#[ORM\Index(name: 'idx_legact_date', columns: ['action_date'])]
class LegislativeAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'legislativeActions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $author = null;

    #[ORM\ManyToOne(targetEntity: Position::class)]
    private ?Position $contextualPosition = null;

    #[ORM\Column(length: 30)]
    private string $type = '';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $actionDate;

    #[ORM\Column(length: 300)]
    private string $titleFr = '';

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(type: 'text')]
    private string $descriptionFr = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descriptionEn = null;

    /** @var Collection<int, Organization> */
    #[ORM\ManyToMany(targetEntity: Organization::class)]
    #[ORM\JoinTable(name: 'legislative_action_beneficiary')]
    private Collection $beneficiaryOrganizations;

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
        $this->beneficiaryOrganizations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?Person
    {
        return $this->author;
    }

    public function setAuthor(?Person $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getContextualPosition(): ?Position
    {
        return $this->contextualPosition;
    }

    public function setContextualPosition(?Position $contextualPosition): self
    {
        $this->contextualPosition = $contextualPosition;

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

    public function getActionDate(): \DateTimeImmutable
    {
        return $this->actionDate;
    }

    public function setActionDate(\DateTimeImmutable $actionDate): self
    {
        $this->actionDate = $actionDate;

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

    public function getDescriptionFr(): string
    {
        return $this->descriptionFr;
    }

    public function setDescriptionFr(string $descriptionFr): self
    {
        $this->descriptionFr = $descriptionFr;

        return $this;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(?string $descriptionEn): self
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    /** @return Collection<int, Organization> */
    public function getBeneficiaryOrganizations(): Collection
    {
        return $this->beneficiaryOrganizations;
    }

    public function addBeneficiaryOrganization(Organization $organization): self
    {
        if (!$this->beneficiaryOrganizations->contains($organization)) {
            $this->beneficiaryOrganizations->add($organization);
        }

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
