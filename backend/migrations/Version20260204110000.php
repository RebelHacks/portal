<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS team (id INT AUTO_INCREMENT NOT NULL, team_name VARCHAR(128) NOT NULL, status VARCHAR(32) DEFAULT \'Unverified\' NOT NULL, UNIQUE INDEX UNIQ_TEAM_NAME (team_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE team');
    }
}
