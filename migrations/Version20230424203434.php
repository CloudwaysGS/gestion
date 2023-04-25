<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230424203434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dette_fournisseur (id INT AUTO_INCREMENT NOT NULL, montant_dette VARCHAR(255) NOT NULL, montant_avance VARCHAR(255) NOT NULL, statut VARCHAR(255) DEFAULT NULL, reste VARCHAR(255) DEFAULT NULL, date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payoff_supplier ADD fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payoff_supplier ADD CONSTRAINT FK_908127FD670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('CREATE INDEX IDX_908127FD670C757F ON payoff_supplier (fournisseur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE dette_fournisseur');
        $this->addSql('ALTER TABLE payoff_supplier DROP FOREIGN KEY FK_908127FD670C757F');
        $this->addSql('DROP INDEX IDX_908127FD670C757F ON payoff_supplier');
        $this->addSql('ALTER TABLE payoff_supplier DROP fournisseur_id');
    }
}
