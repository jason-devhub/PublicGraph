<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Alignement metadata Doctrine / MariaDB (commentaires DC2Type, nom d’index FK wizard).
 */
final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alignement schéma : persons Wikidata, index user_wizard_states';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE persons CHANGE last_wikidata_sync_at last_wikidata_sync_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user_wizard_states CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_wizard_states RENAME INDEX idx_wizard_user TO IDX_6E47A80CA76ED395');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE persons CHANGE last_wikidata_sync_at last_wikidata_sync_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_wizard_states CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_wizard_states RENAME INDEX idx_6e47a80ca76ed395 TO IDX_wizard_user');
    }
}
