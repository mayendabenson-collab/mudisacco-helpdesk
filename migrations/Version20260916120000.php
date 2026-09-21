<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add branches table, ticket branch and channel fields, and member branch field for Customer Service Desk.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE branches (id BLOB NOT NULL, code VARCHAR(32) NOT NULL, name VARCHAR(140) NOT NULL, type VARCHAR(32) NOT NULL, active BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BRANCHES_CODE ON branches (code)');
        $this->addSql('CREATE INDEX idx_branches_active ON branches (active)');

        $this->addSql('ALTER TABLE tickets ADD COLUMN branch_id BLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE tickets ADD COLUMN channel VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_tickets_branch ON tickets (branch_id)');
        $this->addSql('CREATE INDEX idx_tickets_channel ON tickets (channel)');

        $this->addSql('ALTER TABLE members ADD COLUMN branch_id BLOB DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_members_branch ON members (branch_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_members_branch');
        $this->addSql('ALTER TABLE members DROP COLUMN branch_id');

        $this->addSql('DROP INDEX idx_tickets_channel');
        $this->addSql('DROP INDEX idx_tickets_branch');
        $this->addSql('ALTER TABLE tickets DROP COLUMN channel');
        $this->addSql('ALTER TABLE tickets DROP COLUMN branch_id');

        $this->addSql('DROP INDEX idx_branches_active');
        $this->addSql('DROP INDEX UNIQ_BRANCHES_CODE');
        $this->addSql('DROP TABLE branches');
    }
}
