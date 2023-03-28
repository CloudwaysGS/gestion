<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230317041425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture CHANGE quantite quantite DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE qt_stock qt_stock DOUBLE PRECISION NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture CHANGE quantite quantite NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE qt_stock qt_stock NUMERIC(10, 0) NOT NULL, CHANGE total total NUMERIC(10, 0) NOT NULL');
    }
}
