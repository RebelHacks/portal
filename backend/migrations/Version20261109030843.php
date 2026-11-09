<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109030843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline: every table except judging, on an empty database.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, published_at DATETIME NOT NULL, hackathon_id INT NOT NULL, author_id INT DEFAULT NULL, INDEX IDX_4DB9D91C996D90CF (hackathon_id), INDEX IDX_4DB9D91CF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(16) NOT NULL, school VARCHAR(128) NOT NULL, major VARCHAR(128) NOT NULL, attended_before TINYINT NOT NULL, looking_for_team TINYINT DEFAULT NULL, agreed_to_terms_at DATETIME NOT NULL, terms_version VARCHAR(32) NOT NULL, submitted_at DATETIME NOT NULL, checked_in_at DATETIME DEFAULT NULL, waiver_signed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, hackathon_id INT NOT NULL, resume_id INT DEFAULT NULL, INDEX IDX_A45BDDC1A76ED395 (user_id), INDEX IDX_A45BDDC1996D90CF (hackathon_id), INDEX IDX_A45BDDC1D262AF09 (resume_id), INDEX idx_application_hackathon_status (hackathon_id, status), UNIQUE INDEX uniq_application_user_hackathon (user_id, hackathon_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(64) NOT NULL, subject_type VARCHAR(32) NOT NULL, subject_id INT DEFAULT NULL, created_at DATETIME NOT NULL, actor_id INT DEFAULT NULL, INDEX IDX_F6E1C0F510DAF24A (actor_id), INDEX idx_audit_log_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, kind VARCHAR(16) NOT NULL, stored_name VARCHAR(64) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, uploaded_at DATETIME NOT NULL, owner_id INT NOT NULL, UNIQUE INDEX UNIQ_8C9F36101185AF6A (stored_name), INDEX IDX_8C9F36107E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE hackathon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, registration_opens_at DATETIME NOT NULL, registration_closes_at DATETIME NOT NULL, max_team_size INT NOT NULL, capacity INT NOT NULL, INDEX idx_hackathon_starts_at (starts_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE schedule_item (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(128) NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME DEFAULT NULL, location VARCHAR(128) DEFAULT NULL, hackathon_id INT NOT NULL, INDEX IDX_FF530545996D90CF (hackathon_id), INDEX idx_schedule_item_hackathon_starts_at (hackathon_id, starts_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, project_name VARCHAR(80) DEFAULT NULL, description VARCHAR(1500) DEFAULT NULL, tech VARCHAR(255) DEFAULT NULL, tags VARCHAR(255) DEFAULT NULL, github_url VARCHAR(255) DEFAULT NULL, devpost_url VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, team_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_DB055AF3296CD8AE (team_id), INDEX IDX_DB055AF3896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(48) NOT NULL, invite_code VARCHAR(32) NOT NULL, created_at DATETIME NOT NULL, hackathon_id INT NOT NULL, leader_id INT DEFAULT NULL, track_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_C4E0A61F6F21F112 (invite_code), INDEX IDX_C4E0A61F996D90CF (hackathon_id), UNIQUE INDEX UNIQ_C4E0A61F73154ED4 (leader_id), INDEX IDX_C4E0A61F5ED23C43 (track_id), INDEX idx_team_hackathon_track (hackathon_id, track_id), UNIQUE INDEX uniq_team_hackathon_name (hackathon_id, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team_invite (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(16) NOT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, team_id INT NOT NULL, invitee_id INT NOT NULL, INDEX IDX_B1F9570E296CD8AE (team_id), INDEX IDX_B1F9570E7A512022 (invitee_id), UNIQUE INDEX uniq_team_invite_team_invitee (team_id, invitee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE team_member (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, team_id INT NOT NULL, application_id INT NOT NULL, INDEX IDX_6FFBDA1296CD8AE (team_id), UNIQUE INDEX UNIQ_6FFBDA13E030ACD (application_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE track (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, description VARCHAR(255) DEFAULT NULL, beginners_only TINYINT DEFAULT 0 NOT NULL, hackathon_id INT NOT NULL, fallback_track_id INT DEFAULT NULL, INDEX IDX_D6E3F8A6996D90CF (hackathon_id), INDEX IDX_D6E3F8A68086D2EF (fallback_track_id), UNIQUE INDEX uniq_track_hackathon_name (hackathon_id, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, first_name VARCHAR(128) NOT NULL, last_name VARCHAR(128) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91C996D90CF FOREIGN KEY (hackathon_id) REFERENCES hackathon (id)');
        $this->addSql('ALTER TABLE announcement ADD CONSTRAINT FK_4DB9D91CF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1996D90CF FOREIGN KEY (hackathon_id) REFERENCES hackathon (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1D262AF09 FOREIGN KEY (resume_id) REFERENCES file (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F510DAF24A FOREIGN KEY (actor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_item ADD CONSTRAINT FK_FF530545996D90CF FOREIGN KEY (hackathon_id) REFERENCES hackathon (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F996D90CF FOREIGN KEY (hackathon_id) REFERENCES hackathon (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F73154ED4 FOREIGN KEY (leader_id) REFERENCES team_member (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE team_invite ADD CONSTRAINT FK_B1F9570E296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_invite ADD CONSTRAINT FK_B1F9570E7A512022 FOREIGN KEY (invitee_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA13E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6996D90CF FOREIGN KEY (hackathon_id) REFERENCES hackathon (id)');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A68086D2EF FOREIGN KEY (fallback_track_id) REFERENCES track (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91C996D90CF');
        $this->addSql('ALTER TABLE announcement DROP FOREIGN KEY FK_4DB9D91CF675F31B');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1996D90CF');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1D262AF09');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F510DAF24A');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36107E3C61F9');
        $this->addSql('ALTER TABLE schedule_item DROP FOREIGN KEY FK_FF530545996D90CF');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3296CD8AE');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3896DBBDE');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F996D90CF');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F73154ED4');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F5ED23C43');
        $this->addSql('ALTER TABLE team_invite DROP FOREIGN KEY FK_B1F9570E296CD8AE');
        $this->addSql('ALTER TABLE team_invite DROP FOREIGN KEY FK_B1F9570E7A512022');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_6FFBDA1296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_6FFBDA13E030ACD');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6996D90CF');
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A68086D2EF');
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE hackathon');
        $this->addSql('DROP TABLE schedule_item');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_invite');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE user');
    }
}
