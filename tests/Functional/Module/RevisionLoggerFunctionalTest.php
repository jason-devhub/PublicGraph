<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Shared\Entity\Revision;
use App\Shared\Service\RevisionLogger;

final class RevisionLoggerFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testLogPersistsOneRevisionPerDiffField(): void
    {
        $suffix = $this->newUserSuffix();
        $proposer = $this->persistUser($suffix.'p');
        $validator = $this->persistUser($suffix.'v');

        /** @var RevisionLogger $logger */
        $logger = new RevisionLogger($this->entityManager);
        $logger->log('person', 42, [
            'familyName' => ['old' => 'Dupont', 'new' => 'Durand'],
            'givenName' => ['old' => 'Jean', 'new' => 'Jeanne'],
        ], $proposer, $validator);

        $repo = $this->entityManager->getRepository(Revision::class);
        $rows = $repo->findBy(['entityType' => 'person', 'entityId' => 42], ['fieldChanged' => 'ASC']);
        self::assertCount(2, $rows);
        self::assertSame('familyName', $rows[0]->getFieldChanged());
        self::assertSame('Dupont', $rows[0]->getOldValue());
        self::assertSame('Durand', $rows[0]->getNewValue());
        self::assertSame($proposer->getId(), $rows[0]->getProposedBy()->getId());
        self::assertSame($validator->getId(), $rows[0]->getValidatedBy()->getId());
    }

    public function testRevisionEntityHasNoMutableSettersForAuditFields(): void
    {
        $ref = new \ReflectionClass(Revision::class);
        foreach (['entityType', 'fieldChanged', 'oldValue', 'newValue'] as $prop) {
            self::assertFalse(
                $ref->hasMethod('set'.ucfirst($prop)),
                \sprintf('Revision ne doit pas exposer set%s (immutabilité logique).', ucfirst($prop)),
            );
        }
    }
}
