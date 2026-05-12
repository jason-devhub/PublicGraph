<?php

declare(strict_types=1);

namespace App\Shared\Seo;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Shared\I18n\LocalizedContentResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CatalogSeoPresenter
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LocalizedContentResolver $localizedContentResolver,
    ) {
    }

    /**
     * @return array{
     *   title: string,
     *   metaDescription: string,
     *   ogTitle: string,
     *   ogDescription: string,
     *   ogType: string,
     *   canonicalUrl: string,
     *   ogImage: ?string,
     *   jsonLd: string
     * }
     */
    public function buildPersonHead(
        Person $person,
        string $canonicalUrl,
        ?string $absoluteImageUrl,
        string $locale,
    ): array {
        $displayName = trim($person->getUsageName() ?: $person->getGivenName().' '.$person->getFamilyName());

        $approvedMemberships = $person->getMemberships()->filter(static fn (Membership $m): bool => 'approved' === $m->getStatus());
        $approvedPositions = $person->getPositions()->filter(static fn (Position $p): bool => 'approved' === $p->getStatus());

        $metaDescription = $this->buildPersonMetaDescription(
            $person,
            $displayName,
            $approvedMemberships->count(),
            $approvedPositions->count(),
            $locale,
        );

        $jsonLd = $this->buildPersonJsonLd($person, $displayName, $canonicalUrl, $absoluteImageUrl, $locale);

        return [
            'title' => $this->translator->trans('seo.person.title', ['%name%' => $displayName], 'messages', $locale),
            'metaDescription' => $metaDescription,
            'ogTitle' => $this->translator->trans('seo.person.og_title', ['%name%' => $displayName], 'messages', $locale),
            'ogDescription' => $metaDescription,
            'ogType' => 'profile',
            'canonicalUrl' => $canonicalUrl,
            'ogImage' => $absoluteImageUrl,
            'jsonLd' => SafeJsonLdEncoder::encodeArray($jsonLd),
        ];
    }

    /**
     * @return array{
     *   title: string,
     *   metaDescription: string,
     *   ogTitle: string,
     *   ogDescription: string,
     *   ogType: string,
     *   canonicalUrl: string,
     *   ogImage: ?string,
     *   jsonLd: string
     * }
     */
    public function buildOrganizationHead(
        Organization $organization,
        string $canonicalUrl,
        int $memberCount,
        string $locale,
    ): array {
        $name = $this->localizedContentResolver->resolveOrganizationDisplayName($organization, $locale);
        $metaDescription = $this->buildOrganizationMetaDescription($organization, $name, $memberCount, $locale);

        $jsonLd = $this->buildOrganizationJsonLd($organization, $name, $canonicalUrl, $memberCount, $locale);

        return [
            'title' => $this->translator->trans('seo.organization.title', ['%name%' => $name], 'messages', $locale),
            'metaDescription' => $metaDescription,
            'ogTitle' => $this->translator->trans('seo.organization.og_title', ['%name%' => $name], 'messages', $locale),
            'ogDescription' => $metaDescription,
            'ogType' => 'website',
            'canonicalUrl' => $canonicalUrl,
            'ogImage' => null,
            'jsonLd' => SafeJsonLdEncoder::encodeArray($jsonLd),
        ];
    }

    private function buildPersonMetaDescription(
        Person $person,
        string $displayName,
        int $membershipCount,
        int $positionCount,
        string $locale,
    ): string {
        $roleBits = array_slice(array_map(static fn (string $c): string => (string) $c, $person->getRoleCategories()), 0, 3);
        $rolePart = [] !== $roleBits
            ? implode(', ', $roleBits)
            : $this->translator->trans('seo.person.role_fallback', [], 'messages', $locale);

        $orgNames = [];
        foreach ($person->getMemberships() as $m) {
            if ('approved' !== $m->getStatus()) {
                continue;
            }
            $o = $m->getOrganization();
            if (null !== $o) {
                $orgNames[] = $this->localizedContentResolver->resolveOrganizationDisplayName($o, $locale);
            }
        }
        $orgNames = array_values(array_unique($orgNames));
        $sample = array_slice($orgNames, 0, 3);
        $orgPart = [] !== $sample
            ? $this->translator->trans('seo.person.org_sample', ['%orgs%' => implode(', ', $sample).(count($orgNames) > 3 ? '…' : '')], 'messages', $locale)
            : '';

        return $this->translator->trans('seo.person.meta', [
            '%name%' => $displayName,
            '%roles%' => $rolePart,
            '%membership_count%' => (string) $membershipCount,
            '%org_part%' => $orgPart,
            '%position_count%' => (string) $positionCount,
        ], 'messages', $locale);
    }

    private function buildOrganizationMetaDescription(Organization $organization, string $name, int $memberCount, string $locale): string
    {
        $countries = [];
        foreach ($organization->getCountries() as $c) {
            $countries[] = $c->getLocalizedName($locale);
        }
        $countries = array_values(array_unique($countries));
        $countryPart = [] !== $countries
            ? $this->translator->trans('seo.organization.countries', [
                '%list%' => implode(', ', array_slice($countries, 0, 3)).(count($countries) > 3 ? '…' : ''),
            ], 'messages', $locale)
            : '';

        return $this->translator->trans('seo.organization.meta', [
            '%name%' => $name,
            '%type%' => $organization->getType(),
            '%member_count%' => (string) $memberCount,
            '%country_part%' => $countryPart,
        ], 'messages', $locale);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPersonJsonLd(Person $person, string $displayName, string $canonicalUrl, ?string $imageUrl, string $locale): array
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $displayName,
            'url' => $canonicalUrl,
        ];

        if (null !== $imageUrl && '' !== $imageUrl) {
            $payload['image'] = $imageUrl;
        }

        $birth = $person->getBirthDate();
        if (null !== $birth) {
            $payload['birthDate'] = $birth->format('Y-m-d');
        }
        $death = $person->getDeathDate();
        if (null !== $death) {
            $payload['deathDate'] = $death->format('Y-m-d');
        }

        $nationalityCodes = [];
        foreach ($person->getNationalities() as $country) {
            $nationalityCodes[] = $country->getIsoCode();
        }
        if ([] !== $nationalityCodes) {
            $payload['nationality'] = $nationalityCodes;
        }

        $memberOf = [];
        foreach ($person->getMemberships() as $m) {
            if ('approved' !== $m->getStatus()) {
                continue;
            }
            $o = $m->getOrganization();
            if (null !== $o) {
                $memberOf[] = [
                    '@type' => 'Organization',
                    'name' => $this->localizedContentResolver->resolveOrganizationDisplayName($o, $locale),
                ];
            }
        }
        if ([] !== $memberOf) {
            $payload['memberOf'] = $memberOf;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOrganizationJsonLd(Organization $organization, string $name, string $canonicalUrl, int $memberCount, string $locale): array
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $canonicalUrl,
        ];

        $website = $organization->getWebsiteUrl();
        if (null !== $website && '' !== $website) {
            $payload['sameAs'] = $website;
        }

        $founded = $organization->getFoundedYear();
        if (null !== $founded) {
            $payload['foundingDate'] = sprintf('%04d-01-01', $founded);
        }

        $locations = [];
        foreach ($organization->getCountries() as $c) {
            $locations[] = [
                '@type' => 'Place',
                'name' => $c->getLocalizedName($locale),
            ];
        }
        if ([] !== $locations) {
            $payload['location'] = $locations;
        }

        if ($memberCount > 0) {
            $payload['numberOfEmployees'] = [
                '@type' => 'QuantitativeValue',
                'value' => $memberCount,
            ];
        }

        return $payload;
    }
}
