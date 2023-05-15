<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230514125112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27B8FBE502');
        $this->addSql('DROP INDEX IDX_29A5EC27B8FBE502 ON produit');
        $this->addSql('ALTER TABLE produit DROP chargement_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD chargement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27B8FBE502 FOREIGN KEY (chargement_id) REFERENCES chargement (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC27B8FBE502 ON produit (chargement_id)');
    }
}
