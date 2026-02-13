<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invitation table for team invitations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invitation (id SERIAL PRIMARY KEY, team_id INT NOT NULL, invitee_id INT NOT NULL, status VARCHAR(32) DEFAULT \'pending\' NOT NULL)');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2296CD8AE FOREIGN KEY (team_id) REFERENCES "team" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2C4E5765E FOREIGN KEY (invitee_id) REFERENCES "user" (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_INV_TEAM ON invitation (team_id)');
        $this->addSql('CREATE INDEX IDX_INV_INVITEE ON invitation (invitee_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS invitation');
    }
}
