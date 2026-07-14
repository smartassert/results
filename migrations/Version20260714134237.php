<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Job;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714134237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ' . Job::class . '.notifyUrl';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job ADD notify_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job DROP notify_url');
    }
}
