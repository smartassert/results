<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220809171039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Event.label, Event.reference, add Event.reference (as entity)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD reference_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE event DROP label');
        $this->addSql('ALTER TABLE event DROP reference');
        $this->addSql('
            ALTER TABLE event 
                ADD CONSTRAINT FK_3BAE0AA71645DEA9 FOREIGN KEY (reference_id) 
                REFERENCES reference (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('CREATE INDEX IDX_3BAE0AA71645DEA9 ON event (reference_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA71645DEA9');
        $this->addSql('DROP INDEX IDX_3BAE0AA71645DEA9');
        $this->addSql('ALTER TABLE event ADD label TEXT NOT NULL');
        $this->addSql('ALTER TABLE event ADD reference VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE event DROP reference_id');
    }
}
