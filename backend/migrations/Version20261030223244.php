<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030223244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, filepath VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_8C9F3610A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, track VARCHAR(128) DEFAULT NULL, state VARCHAR(128) DEFAULT NULL, major VARCHAR(50) DEFAULT NULL, first_name VARCHAR(128) NOT NULL, last_name VARCHAR(128) NOT NULL, team_id INT DEFAULT NULL, INDEX IDX_8D93D649296CD8AE (team_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649296CD8AE FOREIGN KEY (team_id) REFERENCES `team` (id)');
        $this->addSql('ALTER TABLE invitation CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2296CD8AE FOREIGN KEY (team_id) REFERENCES `team` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A27A512022 FOREIGN KEY (invitee_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F11D61A2296CD8AE ON invitation (team_id)');
        $this->addSql('CREATE INDEX IDX_F11D61A27A512022 ON invitation (invitee_id)');
        $this->addSql('DROP INDEX UNIQ_TEAM_NAME ON team');
        $this->addSql('ALTER TABLE team CHANGE team_name name VARCHAR(128) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TEAM_NAME ON team (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F3610A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649296CD8AE');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE `invitation` DROP FOREIGN KEY FK_F11D61A2296CD8AE');
        $this->addSql('ALTER TABLE `invitation` DROP FOREIGN KEY FK_F11D61A27A512022');
        $this->addSql('DROP INDEX IDX_F11D61A2296CD8AE ON `invitation`');
        $this->addSql('DROP INDEX IDX_F11D61A27A512022 ON `invitation`');
        $this->addSql('ALTER TABLE `invitation` CHANGE id id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_TEAM_NAME ON `team`');
        $this->addSql('ALTER TABLE `team` CHANGE name team_name VARCHAR(128) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TEAM_NAME ON `team` (team_name)');
    }
}
