<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240327113756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entree_depot DROP FOREIGN KEY FK_D59AA5E68510D4DE');
        $this->addSql('ALTER TABLE entree_depot ADD produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entree_depot ADD CONSTRAINT FK_D59AA5E6F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE entree_depot ADD CONSTRAINT FK_D59AA5E68510D4DE FOREIGN KEY (depot_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_D59AA5E6F347EFB ON entree_depot (produit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entree_depot DROP FOREIGN KEY FK_D59AA5E6F347EFB');
        $this->addSql('ALTER TABLE entree_depot DROP FOREIGN KEY FK_D59AA5E68510D4DE');
        $this->addSql('DROP INDEX IDX_D59AA5E6F347EFB ON entree_depot');
        $this->addSql('ALTER TABLE entree_depot DROP produit_id');
        $this->addSql('ALTER TABLE entree_depot ADD CONSTRAINT FK_D59AA5E68510D4DE FOREIGN KEY (depot_id) REFERENCES depot (id)');
    }
}