<?php

declare(strict_types=1);

namespace App\Shared\Twig;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Shared\Seo\CatalogSeoPresenter;
use App\Shared\Seo\SafeJsonLdEncoder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('MetaTags', template: 'shared/components/MetaTags.html.twig')]
final class MetaTags
{
    public Person|Organization|null $entity = null;

    public string $type = 'person';

    public ?int $organizationMemberCount = null;

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        private readonly CatalogSeoPresenter $catalogSeoPresenter,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly array $enabledLocales,
        private readonly string $defaultLocale,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $scheme = $request?->getScheme() ?? 'https';
        $host = $request?->getHost() ?? 'localhost';
        $base = $scheme.'://'.$host;
        $locale = $request?->getLocale() ?? $this->defaultLocale;

        if ($this->entity instanceof Person && 'person' === $this->type) {
            $slug = $this->entity->getSlug();
            if (null === $slug) {
                return $this->defaultHead($locale);
            }

            $canonical = $this->urlGenerator->generate(
                'app_person_show',
                ['slug' => $slug, '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $photo = $this->entity->getPhotoUrl();
            $absoluteImage = null;
            if (null !== $photo && '' !== $photo) {
                $absoluteImage = str_starts_with($photo, 'http') ? $photo : $base.'/'.ltrim($photo, '/');
            }

            $head = $this->catalogSeoPresenter->buildPersonHead($this->entity, $canonical, $absoluteImage, $locale);
            $head['hreflangAlternates'] = $this->personAlternates($slug);
            $head['xDefaultHreflangUrl'] = $this->pickAlternateUrl($head['hreflangAlternates'], $this->defaultLocale);
            $head['ogLocale'] = $this->ogLocaleTag($locale);
            $head['ogLocaleAlternates'] = $this->ogAlternateLocales($locale);

            return $head;
        }

        if ($this->entity instanceof Organization && 'organization' === $this->type) {
            $slug = $this->entity->getSlug();
            if (null === $slug) {
                return $this->defaultHead($locale);
            }

            $canonical = $this->urlGenerator->generate(
                'app_organization_show',
                ['slug' => $slug, '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $memberCount = $this->organizationMemberCount ?? 0;

            $head = $this->catalogSeoPresenter->buildOrganizationHead($this->entity, $canonical, $memberCount, $locale);
            $head['hreflangAlternates'] = $this->organizationAlternates($slug);
            $head['xDefaultHreflangUrl'] = $this->pickAlternateUrl($head['hreflangAlternates'], $this->defaultLocale);
            $head['ogLocale'] = $this->ogLocaleTag($locale);
            $head['ogLocaleAlternates'] = $this->ogAlternateLocales($locale);

            return $head;
        }

        return $this->defaultHead($locale);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultHead(string $locale): array
    {
        $canonical = $this->urlGenerator->generate(
            'app_home',
            ['_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $hreflangAlternates = $this->homeAlternates();

        return [
            'title' => $this->translator->trans('seo.default.title', [], 'messages', $locale),
            'metaDescription' => $this->translator->trans('seo.default.description', [], 'messages', $locale),
            'ogTitle' => $this->translator->trans('seo.default.title', [], 'messages', $locale),
            'ogDescription' => $this->translator->trans('seo.default.description', [], 'messages', $locale),
            'ogType' => 'website',
            'canonicalUrl' => $canonical,
            'ogImage' => null,
            'jsonLd' => SafeJsonLdEncoder::encodeArray(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'PublicGraph']),
            'hreflangAlternates' => $hreflangAlternates,
            'xDefaultHreflangUrl' => $this->pickAlternateUrl($hreflangAlternates, $this->defaultLocale),
            'ogLocale' => $this->ogLocaleTag($locale),
            'ogLocaleAlternates' => $this->ogAlternateLocales($locale),
        ];
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    private function personAlternates(string $slug): array
    {
        $out = [];
        foreach ($this->enabledLocales as $loc) {
            $out[] = [
                'locale' => $loc,
                'url' => $this->urlGenerator->generate(
                    'app_person_show',
                    ['slug' => $slug, '_locale' => $loc],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    private function organizationAlternates(string $slug): array
    {
        $out = [];
        foreach ($this->enabledLocales as $loc) {
            $out[] = [
                'locale' => $loc,
                'url' => $this->urlGenerator->generate(
                    'app_organization_show',
                    ['slug' => $slug, '_locale' => $loc],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{locale: string, url: string}>
     */
    private function homeAlternates(): array
    {
        $out = [];
        foreach ($this->enabledLocales as $loc) {
            $out[] = [
                'locale' => $loc,
                'url' => $this->urlGenerator->generate(
                    'app_home',
                    ['_locale' => $loc],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ];
        }

        return $out;
    }

    private function ogLocaleTag(string $locale): string
    {
        return match ($locale) {
            'en' => 'en_US',
            'fr' => 'fr_FR',
            default => str_replace('-', '_', $locale),
        };
    }

    /**
     * @return list<string>
     */
    private function ogAlternateLocales(string $currentLocale): array
    {
        $tags = [];
        foreach ($this->enabledLocales as $loc) {
            if ($loc === $currentLocale) {
                continue;
            }
            $tags[] = $this->ogLocaleTag($loc);
        }

        return $tags;
    }

    /**
     * @param list<array{locale: string, url: string}> $alternates
     */
    private function pickAlternateUrl(array $alternates, string $locale): string
    {
        foreach ($alternates as $row) {
            if ($row['locale'] === $locale) {
                return $row['url'];
            }
        }

        return $alternates[0]['url'] ?? '';
    }
}
