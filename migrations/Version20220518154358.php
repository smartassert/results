<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Token;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220518154358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Token::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE token (
                token VARCHAR(32) NOT NULL, 
                job_label VARCHAR(32) NOT NULL, 
                user_id VARCHAR(32) NOT NULL, 
                PRIMARY KEY(token, job_label)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F37A13B5F37A13B ON token (token)');
        $this->addSql('CREATE INDEX user_id_idx ON token (user_id)');
        $this->addSql('CREATE UNIQUE INDEX job_label_idx ON token (job_label)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE token');
    }
}
