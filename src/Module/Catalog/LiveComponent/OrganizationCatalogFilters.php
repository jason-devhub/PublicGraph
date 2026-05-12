<?php

declare(strict_types=1);

namespace App\Module\Catalog\LiveComponent;

use App\Module\Catalog\Form\OrganizationCatalogFilterType;
use App\Module\Catalog\Model\OrganizationCatalogFilterModel;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Repository\PersonRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(name: 'CatalogOrganizationList', template: 'catalog/live_component/organization_list.html.twig', method: 'get')]
final class OrganizationCatalogFilters extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly PersonRepository $personRepository,
    ) {
    }

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(OrganizationCatalogFilterType::class, new OrganizationCatalogFilterModel());
    }

    #[LiveAction]
    public function setPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * @return array{pager: Pagerfanta<object>, memberCounts: array<int, int>}
     */
    #[ExposeInTemplate('organizationCatalog')]
    public function buildOrganizationCatalog(): array
    {
        /** @var OrganizationCatalogFilterModel $data */
        $data = $this->getForm()->getData();
        $types = $data->types;
        if (null !== $types && [] === $types) {
            $types = null;
        }

        $countryCodes = [];
        foreach ($data->countries as $country) {
            $countryCodes[] = $country->getIsoCode();
        }

        $qb = $this->organizationRepository->createApprovedCatalogListQueryBuilder($types, $countryCodes);
        $pager = new Pagerfanta(new QueryAdapter($qb, false));
        $pager->setMaxPerPage(30);
        $pager->setCurrentPage(max(1, $this->page));

        $memberCounts = [];
        foreach ($pager->getCurrentPageResults() as $organization) {
            $oid = $organization->getId();
            if (null !== $oid) {
                $memberCounts[$oid] = $this->personRepository->countApprovedMembersForOrganization($organization, null);
            }
        }

        return [
            'pager' => $pager,
            'memberCounts' => $memberCounts,
        ];
    }
}
