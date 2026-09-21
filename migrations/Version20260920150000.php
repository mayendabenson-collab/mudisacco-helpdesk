<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260920150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add teams and team-aware ticket assignment references';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE teams (id BLOB NOT NULL, code VARCHAR(40) NOT NULL, name VARCHAR(120) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN NOT NULL, department_id BLOB NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6FBC9425AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6FBC942577153098 ON teams (code)');
        $this->addSql('CREATE INDEX IDX_6FBC9425AE80F5DF ON teams (department_id)');
        $this->addSql('CREATE TABLE team_members (user_id BLOB NOT NULL, team_id BLOB NOT NULL, PRIMARY KEY(user_id, team_id), CONSTRAINT FK_4D08F6A3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4D08F6A2963B3E4 FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4D08F6A3A76ED395 ON team_members (user_id)');
        $this->addSql('CREATE INDEX IDX_4D08F6A2963B3E4 ON team_members (team_id)');
        $this->addSql('ALTER TABLE tickets ADD COLUMN assigned_team_id BLOB DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_54469DF42963B3E4 ON tickets (assigned_team_id)');
        $this->addSql('ALTER TABLE ticket_assignments ADD COLUMN assigned_team_id BLOB DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_5C1A56A22963B3E4 ON ticket_assignments (assigned_team_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_5C1A56A22963B3E4');
        $this->addSql('ALTER TABLE ticket_assignments DROP COLUMN assigned_team_id');
        $this->addSql('DROP INDEX IDX_54469DF42963B3E4');
        $this->addSql('ALTER TABLE tickets DROP COLUMN assigned_team_id');
        $this->addSql('DROP TABLE team_members');
        $this->addSql('DROP TABLE teams');
    }
}
