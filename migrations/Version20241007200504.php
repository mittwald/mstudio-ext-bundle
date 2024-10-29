<?php

declare(strict_types=1);

namespace Mittwald\MStudio\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007200504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE extension_instance ADD consented_scopes JSON NOT NULL, ADD enabled TINYINT(1) NOT NULL');
        $this->addSql('UPDATE extension_instance SET consented_scopes = "[]", enabled = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE extension_instance DROP consented_scopes, DROP enabled');
    }
}
