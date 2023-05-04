<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230504132626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture2 (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, chargement_id INT DEFAULT NULL, numero_facture VARCHAR(255) DEFAULT NULL, montant VARCHAR(255) DEFAULT NULL, date DATETIME DEFAULT NULL, quantite DOUBLE PRECISION NOT NULL, prix_unit NUMERIC(10, 0) NOT NULL, total NUMERIC(10, 0) NOT NULL, etat VARCHAR(255) DEFAULT NULL, nom_clent VARCHAR(255) DEFAULT NULL, INDEX IDX_79C280D19EB6921 (client_id), INDEX IDX_79C280DB8FBE502 (chargement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE facture2_produit (facture2_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_E23E6558CA7D27A1 (facture2_id), INDEX IDX_E23E6558F347EFB (produit_id), PRIMARY KEY(facture2_id, produit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture2 ADD CONSTRAINT FK_79C280D19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE facture2 ADD CONSTRAINT FK_79C280DB8FBE502 FOREIGN KEY (chargement_id) REFERENCES chargement (id)');
        $this->addSql('ALTER TABLE facture2_produit ADD CONSTRAINT FK_E23E6558CA7D27A1 FOREIGN KEY (facture2_id) REFERENCES facture2 (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture2_produit ADD CONSTRAINT FK_E23E6558F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture2 DROP FOREIGN KEY FK_79C280D19EB6921');
        $this->addSql('ALTER TABLE facture2 DROP FOREIGN KEY FK_79C280DB8FBE502');
        $this->addSql('ALTER TABLE facture2_produit DROP FOREIGN KEY FK_E23E6558CA7D27A1');
        $this->addSql('ALTER TABLE facture2_produit DROP FOREIGN KEY FK_E23E6558F347EFB');
        $this->addSql('DROP TABLE facture2');
        $this->addSql('DROP TABLE facture2_produit');
    }
}
