<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ancienne migration auto-générée déjà exécutée sur certains environnements ;
 * le schéma complet est porté par Version20260511193000 (idempotent).
 */
final class Version20260511182906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op (historique) — schéma via Version20260511193000';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
