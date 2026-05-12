<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schéma initial M2 (entités Doctrine T2.1–T2.9)';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['users'])) {
            return;
        }

        $path = __DIR__.'/SchemaM2.sql';
        $contents = (string) file_get_contents($path);
        $parts = preg_split('/;\s*(?=\R)/', trim($contents));
        foreach ($parts as $sql) {
            $sql = trim($sql);
            if ('' === $sql) {
                continue;
            }
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $tables = [
            'revolving_doors',
            'legislative_action_beneficiary',
            'legislative_actions',
            'entity_sources',
            'sources',
            'person_similarities',
            'change_proposals',
            'right_of_reply_requests',
            'reports',
            'revisions',
            'memberships',
            'positions',
            'person_translations',
            'person_nationality',
            'parties',
            'organization_translations',
            'organization_country',
            'persons',
            'organizations',
            'countries',
            'users',
        ];
        foreach ($tables as $table) {
            $this->addSql('DROP TABLE IF EXISTS '.$table);
        }
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
