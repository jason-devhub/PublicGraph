<?php

declare(strict_types=1);

namespace App\Module\User\Entity;

use App\Module\User\Repository\UserWizardStateRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: UserWizardStateRepository::class)]
#[ORM\Table(name: 'user_wizard_states')]
#[ORM\UniqueConstraint(name: 'uniq_user_wizard_type', columns: ['user_id', 'wizard_type'])]
class UserWizardState
{
    public const WIZARD_PERSON_CREATE = 'person_create';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private string $wizardType = '';

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $stateJson = [];

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWizardType(): string
    {
        return $this->wizardType;
    }

    public function setWizardType(string $wizardType): self
    {
        $this->wizardType = $wizardType;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getStateJson(): array
    {
        return $this->stateJson;
    }

    /** @param array<string, mixed> $stateJson */
    public function setStateJson(array $stateJson): self
    {
        $this->stateJson = $stateJson;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
