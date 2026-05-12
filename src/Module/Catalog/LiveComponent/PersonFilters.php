<?php

declare(strict_types=1);

namespace App\Module\Catalog\LiveComponent;

use App\Module\Catalog\Form\PersonCatalogFilterType;
use App\Module\Catalog\Model\PersonCatalogFilterModel;
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

#[AsLiveComponent(name: 'CatalogPersonFilters', template: 'catalog/live_component/person_filters.html.twig', method: 'get')]
final class PersonFilters extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly PersonRepository $personRepository,
    ) {
    }

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveAction]
    public function setPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(PersonCatalogFilterType::class, new PersonCatalogFilterModel());
    }

    /**
     * @return array{pager: Pagerfanta<object>, topOrgsByPerson: array<int, list<\App\Module\Organization\Entity\Organization>>}
     */
    #[ExposeInTemplate('personCatalog')]
    public function buildPersonCatalog(): array
    {
        /** @var PersonCatalogFilterModel $data */
        $data = $this->getForm()->getData();
        $qb = $this->personRepository->createApprovedCatalogQueryBuilder($data);
        $pager = new Pagerfanta(new QueryAdapter($qb, false));
        $pager->setMaxPerPage(20);
        $pager->setCurrentPage(max(1, $this->page));

        $ids = [];
        foreach ($pager->getCurrentPageResults() as $person) {
            $id = $person->getId();
            if (null !== $id) {
                $ids[] = $id;
            }
        }

        return [
            'pager' => $pager,
            'topOrgsByPerson' => $this->personRepository->loadTopInfluenceOrganizationsByPersonIds($ids),
        ];
    }
}
