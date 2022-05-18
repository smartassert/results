<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Event;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220518132740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Event::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE event (
                id VARCHAR(32) NOT NULL, 
                label VARCHAR(32) NOT NULL, 
                type VARCHAR(32) NOT NULL, 
                reference VARCHAR(32) NOT NULL, 
                payload JSON NOT NULL, PRIMARY KEY(id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event');
    }
}
