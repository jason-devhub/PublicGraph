<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use App\Module\Person\Entity\Person;
use App\Module\Proximity\Entity\PersonSimilarity;
use App\Module\Proximity\Repository\PersonSimilarityRepository;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

final class PersonSimilarityRepositoryFunctionalTest extends KernelFunctionalTestCase
{
    use TestEntitiesTrait;

    public function testFindTopForPersonOrdersByScore(): void
    {
        $suffix = $this->newUserSuffix();
        $p1 = $this->persistPerson($suffix.'1', Person::STATUS_APPROVED);
        $p2 = $this->persistPerson($suffix.'2', Person::STATUS_APPROVED);
        $p3 = $this->persistPerson($suffix.'3', Person::STATUS_APPROVED);

        $this->persistSimilarity($p1, $p2, '10.00', ['k' => 'low']);
        $this->persistSimilarity($p1, $p3, '99.50', ['k' => 'high']);

        $repo = $this->getRepository();
        $top = $repo->findTopForPerson($p1, 10);

        self::assertNotEmpty($top);
        self::assertSame('99.50', $top[0]->getScore());
    }

    public function testDeleteAllForPerson(): void
    {
        $suffix = $this->newUserSuffix();
        $p1 = $this->persistPerson($suffix.'1', Person::STATUS_APPROVED);
        $p2 = $this->persistPerson($suffix.'2', Person::STATUS_APPROVED);

        $this->persistSimilarity($p1, $p2, '50.00', []);
        $this->persistSimilarity($p2, $p1, '40.00', []);

        $repo = $this->getRepository();
        $repo->deleteAllForPerson($p1);
        $this->getEntityManager()->clear();

        $p1r = $this->getEntityManager()->find(Person::class, $p1->getId());
        \assert(null !== $p1r);
        self::assertSame([], $repo->findTopForPerson($p1r, 10));
    }

    public function testBulkInsertCreatesRows(): void
    {
        $suffix = $this->newUserSuffix();
        $p1 = $this->persistPerson($suffix.'1', Person::STATUS_APPROVED);
        $p2 = $this->persistPerson($suffix.'2', Person::STATUS_APPROVED);
        $id1 = (int) $p1->getId();
        $id2 = (int) $p2->getId();

        $repo = $this->getRepository();
        $repo->bulkInsert([
            [
                'personAId' => $id1,
                'personBId' => $id2,
                'score' => '77.25',
                'details' => ['via' => 'bulk'],
            ],
        ]);
        $this->getEntityManager()->clear();

        $p1r = $this->getEntityManager()->find(Person::class, $id1);
        \assert(null !== $p1r);
        $top = $repo->findTopForPerson($p1r, 5);
        self::assertNotEmpty($top);
        self::assertSame('77.25', $top[0]->getScore());
        self::assertSame(['via' => 'bulk'], $top[0]->getDetails());
    }

    #[DoesNotPerformAssertions]
    public function testBulkInsertEmptyArrayIsNoOp(): void
    {
        $repo = $this->getRepository();
        $repo->bulkInsert([]);
    }

    private function persistSimilarity(Person $a, Person $b, string $score, array $details): PersonSimilarity
    {
        $s = new PersonSimilarity();
        $s->setPersonA($a);
        $s->setPersonB($b);
        $s->setScore($score);
        $s->setDetails($details);
        $this->getEntityManager()->persist($s);
        $this->getEntityManager()->flush();

        return $s;
    }

    private function getRepository(): PersonSimilarityRepository
    {
        $repo = $this->getEntityManager()->getRepository(PersonSimilarity::class);
        \assert($repo instanceof PersonSimilarityRepository);

        return $repo;
    }
}
