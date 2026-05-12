<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table user_wizard_states (wizard contribution M4)';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist(['user_wizard_states'])) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE user_wizard_states (
  id INT AUTO_INCREMENT NOT NULL,
  user_id INT NOT NULL,
  wizard_type VARCHAR(50) NOT NULL,
  state_json JSON NOT NULL,
  updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  UNIQUE INDEX uniq_user_wizard_type (user_id, wizard_type),
  INDEX IDX_wizard_user (user_id),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        $this->addSql('ALTER TABLE user_wizard_states ADD CONSTRAINT FK_user_wizard_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_wizard_states');
    }
}
