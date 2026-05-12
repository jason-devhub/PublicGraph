<?php

declare(strict_types=1);

namespace App\Module\User\Entity;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\User\Repository\UserRepository;
use App\Shared\Entity\Revision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email')]
#[UniqueEntity('username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/')]
    private string $username = '';

    #[ORM\Column]
    private string $password = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 20)]
    private string $status = 'active';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $cguAcceptedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Person> */
    #[ORM\OneToMany(targetEntity: Person::class, mappedBy: 'createdBy')]
    private Collection $personsCreated;

    /** @var Collection<int, ChangeProposal> */
    #[ORM\OneToMany(targetEntity: ChangeProposal::class, mappedBy: 'submittedBy')]
    private Collection $changeProposalsSubmitted;

    /** @var Collection<int, ChangeProposal> */
    #[ORM\OneToMany(targetEntity: ChangeProposal::class, mappedBy: 'moderatedBy')]
    private Collection $changeProposalsModerated;

    /** @var Collection<int, EntitySource> */
    #[ORM\OneToMany(targetEntity: EntitySource::class, mappedBy: 'addedBy')]
    private Collection $entitySourcesAdded;

    /** @var Collection<int, Revision> */
    #[ORM\OneToMany(targetEntity: Revision::class, mappedBy: 'proposedBy')]
    private Collection $revisionsProposed;

    /** @var Collection<int, Revision> */
    #[ORM\OneToMany(targetEntity: Revision::class, mappedBy: 'validatedBy')]
    private Collection $revisionsValidated;

    public function __construct()
    {
        $this->personsCreated = new ArrayCollection();
        $this->changeProposalsSubmitted = new ArrayCollection();
        $this->changeProposalsModerated = new ArrayCollection();
        $this->entitySourcesAdded = new ArrayCollection();
        $this->revisionsProposed = new ArrayCollection();
        $this->revisionsValidated = new ArrayCollection();
        $this->cguAcceptedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function __toString(): string
    {
        return $this->email;
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
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

    public function getCguAcceptedAt(): \DateTimeImmutable
    {
        return $this->cguAcceptedAt;
    }

    public function setCguAcceptedAt(\DateTimeImmutable $cguAcceptedAt): self
    {
        $this->cguAcceptedAt = $cguAcceptedAt;

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): self
    {
        $this->emailVerificationToken = $emailVerificationToken;

        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?\DateTimeImmutable $emailVerificationTokenExpiresAt): self
    {
        $this->emailVerificationTokenExpiresAt = $emailVerificationTokenExpiresAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::v7();
        }
    }
}
