<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add track, project, and judge assignment fields to team table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('team');

        if (!$table->hasColumn('track')) {
            $this->addSql("ALTER TABLE team ADD track VARCHAR(32) DEFAULT 'Software' NOT NULL");
        }

        if (!$table->hasColumn('project_name')) {
            $this->addSql('ALTER TABLE team ADD project_name VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('project_details')) {
            $this->addSql('ALTER TABLE team ADD project_details LONGTEXT DEFAULT NULL');
        }

        if (!$table->hasColumn('judge_assignments')) {
            $this->addSql('ALTER TABLE team ADD judge_assignments JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('team');

        if ($table->hasColumn('judge_assignments')) {
            $this->addSql('ALTER TABLE team DROP judge_assignments');
        }

        if ($table->hasColumn('project_details')) {
            $this->addSql('ALTER TABLE team DROP project_details');
        }

        if ($table->hasColumn('project_name')) {
            $this->addSql('ALTER TABLE team DROP project_name');
        }

        if ($table->hasColumn('track')) {
            $this->addSql('ALTER TABLE team DROP track');
        }
    }
}
