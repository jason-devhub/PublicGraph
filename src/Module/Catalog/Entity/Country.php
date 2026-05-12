<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'countries')]
class Country
{
    #[ORM\Id]
    #[ORM\Column(length: 2)]
    private string $isoCode;

    #[ORM\Column(length: 100)]
    private string $nameFr;

    #[ORM\Column(length: 100)]
    private string $nameEn;

    #[ORM\Column(length: 30)]
    private string $continent;

    /** @var Collection<int, Person> */
    #[ORM\ManyToMany(targetEntity: Person::class, mappedBy: 'nationalities')]
    private Collection $persons;

    /** @var Collection<int, Organization> */
    #[ORM\ManyToMany(targetEntity: Organization::class, mappedBy: 'countries')]
    private Collection $organizations;

    /** @var Collection<int, Position> */
    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: 'country')]
    private Collection $positions;

    public function __construct(string $isoCode, string $nameFr, string $nameEn, string $continent)
    {
        $this->isoCode = strtoupper($isoCode);
        $this->nameFr = $nameFr;
        $this->nameEn = $nameEn;
        $this->continent = $continent;
        $this->persons = new ArrayCollection();
        $this->organizations = new ArrayCollection();
        $this->positions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nameFr.' ('.$this->isoCode.')';
    }

    public function getIsoCode(): string
    {
        return $this->isoCode;
    }

    public function getNameFr(): string
    {
        return $this->nameFr;
    }

    public function setNameFr(string $nameFr): self
    {
        $this->nameFr = $nameFr;

        return $this;
    }

    public function getNameEn(): string
    {
        return $this->nameEn;
    }

    public function getLocalizedName(string $locale): string
    {
        return match ($locale) {
            'en' => '' !== trim($this->nameEn) ? $this->nameEn : $this->nameFr,
            'fr' => '' !== trim($this->nameFr) ? $this->nameFr : $this->nameEn,
            default => $this->nameFr,
        };
    }

    public function setNameEn(string $nameEn): self
    {
        $this->nameEn = $nameEn;

        return $this;
    }

    public function getContinent(): string
    {
        return $this->continent;
    }

    public function setContinent(string $continent): self
    {
        $this->continent = $continent;

        return $this;
    }

    /** @return Collection<int, Person> */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    /** @return Collection<int, Organization> */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    /** @return Collection<int, Position> */
    public function getPositions(): Collection
    {
        return $this->positions;
    }
}
