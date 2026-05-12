<?php

declare(strict_types=1);

namespace App\Shared\I18n;

use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\LegislativeAction;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\OrganizationTranslation;
use App\Module\Person\Entity\Person;
use App\Module\Person\Entity\PersonTranslation;

/**
 * Résolution de textes BDD selon la locale, avec repli selon l’ordre de APP_ENABLED_LOCALES.
 */
final class LocalizedContentResolver
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly array $enabledLocales,
    ) {
    }

    public function resolvePersonDescription(Person $person, string $locale): ?string
    {
        $map = $this->translationsByLocale($person);
        $text = $this->firstNonEmptyString($map, $locale, fn (PersonTranslation $t): ?string => $this->nonEmptyTrimmed($t->getDescription()));

        if (null !== $text) {
            return $text;
        }

        return $this->firstNonEmptyString($map, $locale, fn (PersonTranslation $t): ?string => $this->nonEmptyTrimmed($t->getBiographySummary()));
    }

    public function resolveOrganizationDescription(Organization $organization, string $locale): ?string
    {
        $map = [];
        foreach ($organization->getTranslations() as $tr) {
            $map[$tr->getLocale()] = $tr;
        }

        return $this->firstNonEmptyString($map, $locale, fn (OrganizationTranslation $t): ?string => $this->nonEmptyTrimmed($t->getDescription()));
    }

    public function resolveOrganizationDisplayName(Organization $organization, string $locale): string
    {
        $map = [];
        foreach ($organization->getTranslations() as $tr) {
            $map[$tr->getLocale()] = $tr;
        }

        $name = $this->firstNonEmptyString($map, $locale, fn (OrganizationTranslation $t): ?string => $this->nonEmptyTrimmed($t->getName()));
        if (null !== $name) {
            return $name;
        }

        return $organization->getOfficialName();
    }

    public function resolvePositionTitle(Position $position, string $locale): string
    {
        if ('en' === $locale) {
            $en = $position->getTitleEn();
            if (null !== $en && '' !== trim($en)) {
                return trim($en);
            }

            return trim($position->getTitleFr());
        }

        $fr = trim($position->getTitleFr());
        if ('' !== $fr) {
            return $fr;
        }
        $en = $position->getTitleEn();

        return null !== $en && '' !== trim($en) ? trim($en) : '';
    }

    public function resolveLegislativeActionTitle(LegislativeAction $action, string $locale): string
    {
        if ('en' === $locale) {
            $en = $action->getTitleEn();
            if (null !== $en && '' !== trim($en)) {
                return trim($en);
            }
        }

        return trim($action->getTitleFr());
    }

    public function resolveLegislativeActionDescription(LegislativeAction $action, string $locale): string
    {
        if ('en' === $locale) {
            $en = $action->getDescriptionEn();
            if (null !== $en && '' !== trim($en)) {
                return trim($en);
            }
        }

        return trim($action->getDescriptionFr());
    }

    public function resolveRevolvingDoorFactualNote(RevolvingDoor $door, string $locale): ?string
    {
        if ('en' === $locale) {
            $en = $this->nonEmptyTrimmed($door->getFactualNoteEn());
            if (null !== $en) {
                return $en;
            }
        }

        return $this->nonEmptyTrimmed($door->getFactualNoteFr());
    }

    /**
     * @return array<string, PersonTranslation>
     */
    private function translationsByLocale(Person $person): array
    {
        $map = [];
        foreach ($person->getTranslations() as $t) {
            $map[$t->getLocale()] = $t;
        }

        return $map;
    }

    /**
     * @template T of object
     *
     * @param array<string, T>     $byLocale
     * @param callable(T): ?string $extract
     */
    private function firstNonEmptyString(array $byLocale, string $locale, callable $extract): ?string
    {
        foreach ($this->fallbackLocaleOrder($locale) as $tryLocale) {
            $obj = $byLocale[$tryLocale] ?? null;
            if (null === $obj) {
                continue;
            }
            $value = $extract($obj);
            if (null !== $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function fallbackLocaleOrder(string $preferred): array
    {
        $order = [$preferred];
        foreach ($this->enabledLocales as $l) {
            if ($l !== $preferred) {
                $order[] = $l;
            }
        }

        return $order;
    }

    private function nonEmptyTrimmed(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }
        $t = trim($value);

        return '' === $t ? null : $t;
    }
}
