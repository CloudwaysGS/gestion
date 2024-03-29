<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240328182142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sortie_depot (id INT AUTO_INCREMENT NOT NULL, depot_id INT DEFAULT NULL, qt_sortie VARCHAR(255) NOT NULL, libelle VARCHAR(255) NOT NULL, INDEX IDX_DE9A2EB88510D4DE (depot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sortie_depot ADD CONSTRAINT FK_DE9A2EB88510D4DE FOREIGN KEY (depot_id) REFERENCES depot (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_depot DROP FOREIGN KEY FK_DE9A2EB88510D4DE');
        $this->addSql('DROP TABLE sortie_depot');
    }
}
