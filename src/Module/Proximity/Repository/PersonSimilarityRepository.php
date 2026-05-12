<?php

declare(strict_types=1);

namespace App\Module\Proximity\Repository;

use App\Module\Person\Entity\Person;
use App\Module\Proximity\Entity\PersonSimilarity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonSimilarity>
 */
class PersonSimilarityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonSimilarity::class);
    }

    /** @return list<PersonSimilarity> */
    public function findTopForPerson(Person $person, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.personA = :p')
            ->setParameter('p', $person)
            ->orderBy('s.score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function deleteAllForPerson(Person $person): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->andWhere('s.personA = :p OR s.personB = :p')
            ->setParameter('p', $person)
            ->getQuery()
            ->execute();
    }

    /**
     * @param list<array{personAId: int, personBId: int, score: string, details: array<string, mixed>}> $rows
     */
    public function bulkInsert(array $rows): void
    {
        if ([] === $rows) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach ($rows as $row) {
            $conn->insert('person_similarities', [
                'person_a_id' => $row['personAId'],
                'person_b_id' => $row['personBId'],
                'score' => $row['score'],
                'details' => json_encode($row['details'], \JSON_THROW_ON_ERROR),
                'computed_at' => $now,
            ], [
                'person_a_id' => ParameterType::INTEGER,
                'person_b_id' => ParameterType::INTEGER,
                'score' => ParameterType::STRING,
                'details' => ParameterType::STRING,
                'computed_at' => ParameterType::STRING,
            ]);
        }
    }
}
