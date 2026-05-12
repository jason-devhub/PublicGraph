<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Legislation\Entity\LegislativeAction;
use App\Module\Legislation\Repository\LegislativeActionRepository;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\Source;
use App\Module\Source\Repository\SourceRepository;

/**
 * Repositories sans méthodes utilitaires : vérification d'enregistrement Doctrine et accès typé.
 */
final class SimpleRepositoriesFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testLegislativeActionRepositoryFindReturnsPersistedEntity(): void
    {
        $suffix = $this->newUserSuffix();
        $author = $this->persistPerson($suffix.'a', Person::STATUS_APPROVED);
        $action = new LegislativeAction();
        $action->setAuthor($author);
        $action->setType('vote');
        $action->setActionDate(new \DateTimeImmutable('2024-03-01'));
        $action->setTitleFr('Titre');
        $action->setDescriptionFr('Description');
        $this->entityManager->persist($action);
        $this->entityManager->flush();

        $repo = $this->entityManager->getRepository(LegislativeAction::class);
        \assert($repo instanceof LegislativeActionRepository);
        $found = $repo->find($action->getId());
        self::assertInstanceOf(LegislativeAction::class, $found);
        self::assertSame('vote', $found->getType());
    }

    public function testSourceRepositoryFindReturnsPersistedEntity(): void
    {
        $suffix = $this->newUserSuffix();
        $source = new Source();
        $source->setUrl(\sprintf('https://press.example/%s', $suffix));
        $source->setType(Source::TYPE_PRESS_ARTICLE);
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        $repo = $this->entityManager->getRepository(Source::class);
        \assert($repo instanceof SourceRepository);
        $found = $repo->find($source->getId());
        self::assertInstanceOf(Source::class, $found);
        self::assertStringContainsString('press.example', $found->getUrl());
    }
}
