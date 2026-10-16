<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016061533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace user name with first_name and last_name, preserving existing names';
    }

    public function isTransactional(): bool
    {
        // transaction can't undo our changes :(
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user ADD first_name VARCHAR(128) DEFAULT NULL, 
            ADD last_name VARCHAR(128) DEFAULT NULL'
        );
        // Legacy values include usernames
        $this->addSql("UPDATE user SET first_name = COALESCE(name, ''), last_name = ''");
        $this->addSql('ALTER TABLE user MODIFY first_name VARCHAR(128) NOT NULL, MODIFY last_name VARCHAR(128) NOT NULL, DROP name');
    }

    public function down(Schema $schema): void
    {
        $nameExpression = "CONCAT(first_name, CASE WHEN CHAR_LENGTH(first_name) > 0 AND CHAR_LENGTH(last_name) > 0 THEN ' ' ELSE '' END, last_name)";
        $this->abortIf(
            (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user WHERE CHAR_LENGTH('.$nameExpression.') > 128') > 0,
            'Cannot restore name: a combined first and last name exceeds the old 128-character limit.'
        );

        $this->addSql('ALTER TABLE user ADD name VARCHAR(128) DEFAULT NULL');
        $this->addSql('UPDATE user SET name = '.$nameExpression);
        $this->addSql('ALTER TABLE user DROP first_name, DROP last_name');
    }
}
