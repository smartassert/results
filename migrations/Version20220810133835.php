<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220810133835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Event.relatedReferences';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE event_reference (
                event_id VARCHAR(32) NOT NULL, 
                reference_id BIGINT NOT NULL, 
                PRIMARY KEY(event_id, reference_id)
            )
        ');

        $this->addSql('CREATE INDEX IDX_2836772E71F7E88B ON event_reference (event_id)');
        $this->addSql('CREATE INDEX IDX_2836772E1645DEA9 ON event_reference (reference_id)');

        $this->addSql('
            ALTER TABLE event_reference 
                ADD CONSTRAINT FK_2836772E71F7E88B FOREIGN KEY (event_id) 
                REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');

        $this->addSql('
            ALTER TABLE event_reference 
                ADD CONSTRAINT FK_2836772E1645DEA9 FOREIGN KEY (reference_id) 
                REFERENCES reference (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
       ');
        $this->addSql('ALTER TABLE event ALTER type TYPE VARCHAR(32)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_reference');
        $this->addSql('ALTER TABLE event ALTER type TYPE VARCHAR(255)');
    }
}
