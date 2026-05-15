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
        $chunkSize = 200;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $placeholders = [];
            $params = [];
            $types = [];
            foreach ($chunk as $i => $row) {
                $prefix = 'r'.$i.'_';
                $placeholders[] = '(:'.$prefix.'a, :'.$prefix.'b, :'.$prefix.'s, :'.$prefix.'d, :'.$prefix.'t)';
                $params[$prefix.'a'] = $row['personAId'];
                $params[$prefix.'b'] = $row['personBId'];
                $params[$prefix.'s'] = $row['score'];
                $params[$prefix.'d'] = json_encode($row['details'], \JSON_THROW_ON_ERROR);
                $params[$prefix.'t'] = $now;
                $types[$prefix.'a'] = ParameterType::INTEGER;
                $types[$prefix.'b'] = ParameterType::INTEGER;
                $types[$prefix.'s'] = ParameterType::STRING;
                $types[$prefix.'d'] = ParameterType::STRING;
                $types[$prefix.'t'] = ParameterType::STRING;
            }
            $sql = 'INSERT INTO person_similarities (person_a_id, person_b_id, score, details, computed_at) VALUES '
                .implode(', ', $placeholders);
            $conn->executeStatement($sql, $params, $types);
        }
    }
}
