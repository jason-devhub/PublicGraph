<?php

declare(strict_types=1);

namespace App\Module\Catalog\LiveComponent;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Repository\PersonRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(name: 'CatalogOrganizationMembers', template: 'catalog/live_component/organization_members.html.twig', method: 'get')]
final class OrganizationMembers
{
    use DefaultActionTrait;

    public function __construct(
        private readonly PersonRepository $personRepository,
    ) {
    }

    #[LiveProp]
    public Organization $organization;

    #[LiveProp(writable: true, url: true)]
    public ?string $memberYear = null;

    #[LiveProp(writable: true, url: true)]
    public int $membersPage = 1;

    #[LiveAction]
    public function setMembersPage(#[LiveArg] int $membersPage): void
    {
        $this->membersPage = max(1, $membersPage);
    }

    /**
     * @return array{yearParticipation: array<int, list<int>>, pager: Pagerfanta<object>}
     */
    #[ExposeInTemplate('memberBlock')]
    public function buildMemberBlock(): array
    {
        $year = null !== $this->memberYear && '' !== $this->memberYear ? (int) $this->memberYear : null;
        $qb = $this->personRepository->createApprovedMembersQueryBuilder($this->organization, $year);
        $pager = new Pagerfanta(new QueryAdapter($qb, false));
        $pager->setMaxPerPage(50);
        $pager->setCurrentPage(max(1, $this->membersPage));

        /** @var list<\App\Module\Person\Entity\Person> $persons */
        $persons = iterator_to_array($pager->getCurrentPageResults());

        $yearParticipation = [];
        foreach ($persons as $person) {
            $pid = $person->getId();
            if (null === $pid) {
                continue;
            }
            $years = [];
            foreach ($person->getMemberships() as $m) {
                if ('approved' !== $m->getStatus()) {
                    continue;
                }
                if ($m->getOrganization()?->getId() !== $this->organization->getId()) {
                    continue;
                }
                if (null !== $m->getYear()) {
                    $years[] = $m->getYear();
                }
            }
            foreach ($person->getPositions() as $pos) {
                if ('approved' !== $pos->getStatus()) {
                    continue;
                }
                if ($pos->getOrganization()?->getId() !== $this->organization->getId()) {
                    continue;
                }
                $years[] = (int) $pos->getStartDate()->format('Y');
            }
            $years = array_values(array_unique($years));
            rsort($years);
            $yearParticipation[$pid] = $years;
        }

        return [
            'yearParticipation' => $yearParticipation,
            'pager' => $pager,
        ];
    }
}
