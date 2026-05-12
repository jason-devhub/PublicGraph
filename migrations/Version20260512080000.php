<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'M5 : champs Wikidata incremental sync sur persons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE persons ADD last_wikidata_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE persons ADD wikidata_manually_edited_fields JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE persons DROP last_wikidata_sync_at');
        $this->addSql('ALTER TABLE persons DROP wikidata_manually_edited_fields');
    }
}
