<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Job;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220518154358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Job::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE job (
                token VARCHAR(32) NOT NULL, 
                label VARCHAR(32) NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                PRIMARY KEY(token, label)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX token_idx ON job (token)');
        $this->addSql('CREATE INDEX user_id_idx ON job (user_id)');
        $this->addSql('CREATE UNIQUE INDEX label_idx ON job (label)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job');
    }
}
