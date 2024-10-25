<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241025185457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add field image to event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD image VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP image');
    }
}
