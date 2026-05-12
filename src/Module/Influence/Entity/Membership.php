<?php

declare(strict_types=1);

namespace App\Module\Influence\Entity;

use App\Module\Influence\Repository\MembershipRepository;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use App\Shared\Validator\MinSources;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MinSources(count: 1)]
#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\Table(name: 'memberships')]
#[ORM\Index(name: 'idx_membership_pair', columns: ['person_id', 'organization_id'])]
#[ORM\Index(name: 'idx_membership_year', columns: ['year'])]
#[ORM\Index(name: 'idx_membership_start', columns: ['start_date'])]
class Membership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Organization $organization = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $year = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $roleInOrganization = null;

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

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
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

    public function getRoleInOrganization(): ?string
    {
        return $this->roleInOrganization;
    }

    public function setRoleInOrganization(?string $roleInOrganization): self
    {
        $this->roleInOrganization = $roleInOrganization;

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
