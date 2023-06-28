<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Reference;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220803163533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create table for ' . Reference::class;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE reference (
                id VARCHAR(32) NOT NULL, 
                label TEXT NOT NULL, 
                reference VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX label_reference_unique ON reference (label, reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reference');
    }
}
