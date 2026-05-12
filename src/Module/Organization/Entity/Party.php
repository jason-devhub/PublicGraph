<?php

declare(strict_types=1);

namespace App\Module\Organization\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'parties')]
class Party
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'partyDetails', targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Organization $organization = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $europeanFamily = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $internationalFamily = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $colorHex = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEuropeanFamily(): ?string
    {
        return $this->europeanFamily;
    }

    public function setEuropeanFamily(?string $europeanFamily): self
    {
        $this->europeanFamily = $europeanFamily;

        return $this;
    }

    public function getInternationalFamily(): ?string
    {
        return $this->internationalFamily;
    }

    public function setInternationalFamily(?string $internationalFamily): self
    {
        $this->internationalFamily = $internationalFamily;

        return $this;
    }

    public function getColorHex(): ?string
    {
        return $this->colorHex;
    }

    public function setColorHex(?string $colorHex): self
    {
        $this->colorHex = $colorHex;

        return $this;
    }
}
