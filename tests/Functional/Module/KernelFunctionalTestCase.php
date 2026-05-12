<?php

declare(strict_types=1);

namespace App\Tests\Functional\Module;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class KernelFunctionalTestCase extends KernelTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        try {
            $em = self::getContainer()->get(EntityManagerInterface::class);
            $em->getConnection()->executeQuery('SELECT 1');
            \assert($em instanceof EntityManagerInterface);
            $this->entityManager = $em;
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de données indisponible pour ces tests : '.$e->getMessage());
        }
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        \assert(null !== $this->entityManager);

        return $this->entityManager;
    }
}
