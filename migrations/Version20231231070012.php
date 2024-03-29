<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231231070012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EB8FBE502');
        $this->addSql('DROP INDEX IDX_B1DC7A1EB8FBE502 ON paiement');
        $this->addSql('ALTER TABLE paiement DROP chargement_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paiement ADD chargement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EB8FBE502 FOREIGN KEY (chargement_id) REFERENCES chargement (id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1EB8FBE502 ON paiement (chargement_id)');
    }
}
