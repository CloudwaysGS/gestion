<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240604112439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chargement ADD avance NUMERIC(10, 0) DEFAULT NULL, ADD reste NUMERIC(10, 0) DEFAULT NULL, ADD dette_impaye NUMERIC(10, 0) DEFAULT NULL');
        $this->addSql('ALTER TABLE dette CHANGE montant_avance tag NUMERIC(10, 0) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chargement DROP avance, DROP reste, DROP dette_impaye');
        $this->addSql('ALTER TABLE dette CHANGE tag montant_avance NUMERIC(10, 0) DEFAULT NULL');
    }
}
