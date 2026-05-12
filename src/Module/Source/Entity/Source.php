<?php

declare(strict_types=1);

namespace App\Module\Source\Entity;

use App\Module\Source\Repository\SourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\Table(name: 'sources')]
#[ORM\HasLifecycleCallbacks]
class Source
{
    public const TYPE_OFFICIAL_PUBLICATION = 'official_publication';

    public const TYPE_PRESS_ARTICLE = 'press_article';

    public const TYPE_WIKIPEDIA = 'wikipedia';

    public const TYPE_WIKIDATA = 'wikidata';

    public const TYPE_BOOK = 'book';

    public const TYPE_REPORT = 'report';

    public const TYPE_OTHER = 'other';

    public const CHECK_UNCHECKED = 'unchecked';

    public const CHECK_LIVE = 'live';

    public const CHECK_DEAD = 'dead';

    public const CHECK_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1000)]
    #[Assert\Url]
    private string $url = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_OTHER;

    #[ORM\Column(length: 200)]
    private string $domain = '';

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $accessedAt;

    #[ORM\Column(length: 20)]
    private string $checkStatus = self::CHECK_UNCHECKED;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastCheckedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $waybackUrl = null;

    /** @var Collection<int, EntitySource> */
    #[ORM\OneToMany(targetEntity: EntitySource::class, mappedBy: 'source', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entitySources;

    public function __construct()
    {
        $this->accessedAt = new \DateTimeImmutable('today');
        $this->entitySources = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getAccessedAt(): \DateTimeImmutable
    {
        return $this->accessedAt;
    }

    public function setAccessedAt(\DateTimeImmutable $accessedAt): self
    {
        $this->accessedAt = $accessedAt;

        return $this;
    }

    public function getCheckStatus(): string
    {
        return $this->checkStatus;
    }

    public function setCheckStatus(string $checkStatus): self
    {
        $this->checkStatus = $checkStatus;

        return $this;
    }

    public function getLastCheckedAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckedAt;
    }

    public function setLastCheckedAt(?\DateTimeImmutable $lastCheckedAt): self
    {
        $this->lastCheckedAt = $lastCheckedAt;

        return $this;
    }

    public function getWaybackUrl(): ?string
    {
        return $this->waybackUrl;
    }

    public function setWaybackUrl(?string $waybackUrl): self
    {
        $this->waybackUrl = $waybackUrl;

        return $this;
    }

    /** @return Collection<int, EntitySource> */
    public function getEntitySources(): Collection
    {
        return $this->entitySources;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshDomain(): void
    {
        $host = parse_url($this->url, \PHP_URL_HOST);
        $this->domain = \is_string($host) && '' !== $host ? $host : 'invalid';
    }
}
