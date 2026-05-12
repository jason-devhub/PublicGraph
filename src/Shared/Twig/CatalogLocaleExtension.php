<?php

declare(strict_types=1);

namespace App\Shared\Twig;

use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Organization\Entity\Organization;
use App\Shared\I18n\LocalizedContentResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class CatalogLocaleExtension extends AbstractExtension
{
    public function __construct(
        private readonly LocalizedContentResolver $localizedContentResolver,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localized_position_title', $this->localizedPositionTitle(...)),
            new TwigFilter('localized_organization_name', $this->localizedOrganizationName(...)),
            new TwigFilter('localized_revolving_door_note', $this->localizedRevolvingDoorNote(...)),
        ];
    }

    public function localizedPositionTitle(Position $position): string
    {
        return $this->localizedContentResolver->resolvePositionTitle($position, $this->currentLocale());
    }

    public function localizedOrganizationName(Organization $organization): string
    {
        return $this->localizedContentResolver->resolveOrganizationDisplayName($organization, $this->currentLocale());
    }

    public function localizedRevolvingDoorNote(RevolvingDoor $door): ?string
    {
        return $this->localizedContentResolver->resolveRevolvingDoorFactualNote($door, $this->currentLocale());
    }

    private function currentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->getLocale() ?? 'en';
    }
}
