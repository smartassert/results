<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Token;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220518141642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Token::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE token (
                job_label VARCHAR(32) NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                PRIMARY KEY(job_label)
            )
        ');
        $this->addSql('CREATE INDEX user_id_idx ON token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX job_label_idx ON token (job_label)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE token');
    }
}
