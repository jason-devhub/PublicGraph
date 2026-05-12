<?php

declare(strict_types=1);

namespace App\Module\Search\EventListener;

use App\Module\Influence\Entity\Membership;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Entity\OrganizationTranslation;
use App\Module\Person\Entity\Person;
use App\Module\Person\Entity\PersonTranslation;
use App\Module\Search\Service\SearchIndexUpdater;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SearchIndexEntitySubscriber implements EventSubscriber
{
    public function __construct(
        private readonly SearchIndexUpdater $searchIndexUpdater,
        #[Autowire(param: 'search.index.enabled')]
        private readonly bool $searchIndexEnabled,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist => 'postPersist',
            Events::postUpdate => 'postUpdate',
            Events::postRemove => 'postRemove',
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        if (!$this->searchIndexEnabled) {
            return;
        }
        $this->handleEntity($args->getObject(), false);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (!$this->searchIndexEnabled) {
            return;
        }
        $this->handleEntity($args->getObject(), false);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        if (!$this->searchIndexEnabled) {
            return;
        }
        $this->handleEntity($args->getObject(), true);
    }

    private function handleEntity(object $entity, bool $isRemove): void
    {
        if ($entity instanceof Person) {
            if ($isRemove) {
                $id = $entity->getId();
                if (null !== $id) {
                    $this->searchIndexUpdater->removePerson($id);
                }

                return;
            }
            $this->searchIndexUpdater->syncPerson($entity);

            return;
        }

        if ($entity instanceof Organization) {
            if ($isRemove) {
                $id = $entity->getId();
                if (null !== $id) {
                    $this->searchIndexUpdater->removeOrganization($id);
                }

                return;
            }
            $this->searchIndexUpdater->syncOrganization($entity);

            return;
        }

        if ($entity instanceof PersonTranslation) {
            $person = $entity->getPerson();
            if (null !== $person) {
                $this->searchIndexUpdater->syncPerson($person);
            }

            return;
        }

        if ($entity instanceof OrganizationTranslation) {
            $organization = $entity->getOrganization();
            if (null !== $organization) {
                $this->searchIndexUpdater->syncOrganization($organization);
            }

            return;
        }

        if ($entity instanceof Membership) {
            $person = $entity->getPerson();
            if (null !== $person) {
                $this->searchIndexUpdater->syncPerson($person);
            }
        }
    }
}
