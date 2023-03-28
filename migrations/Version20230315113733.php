<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230315113733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chargement (id INT AUTO_INCREMENT NOT NULL, nombre INT NOT NULL, nom_client VARCHAR(255) DEFAULT NULL, date DATETIME NOT NULL, total NUMERIC(10, 0) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE facture_produit (facture_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_61424D7E7F2DEE08 (facture_id), INDEX IDX_61424D7EF347EFB (produit_id), PRIMARY KEY(facture_id, produit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture_produit ADD CONSTRAINT FK_61424D7E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_produit ADD CONSTRAINT FK_61424D7EF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client ADD telephone VARCHAR(255) NOT NULL, DROP pays, CHANGE ville ville VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dette ADD statut VARCHAR(255) DEFAULT NULL, DROP paye');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664107F2DEE08');
        $this->addSql('DROP INDEX IDX_FE8664107F2DEE08 ON facture');
        $this->addSql('ALTER TABLE facture ADD montant VARCHAR(255) DEFAULT NULL, ADD date DATETIME DEFAULT NULL, ADD prix_unit NUMERIC(10, 0) NOT NULL, ADD total NUMERIC(10, 0) DEFAULT NULL, ADD nom_client VARCHAR(255) DEFAULT NULL, ADD etat VARCHAR(255) NOT NULL, DROP date_facture, CHANGE numero_facture numero_facture VARCHAR(255) DEFAULT NULL, CHANGE facture_id chargement_id INT DEFAULT NULL, CHANGE montant_facture quantite NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410B8FBE502 FOREIGN KEY (chargement_id) REFERENCES chargement (id)');
        $this->addSql('CREATE INDEX IDX_FE866410B8FBE502 ON facture (chargement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410B8FBE502');
        $this->addSql('ALTER TABLE facture_produit DROP FOREIGN KEY FK_61424D7E7F2DEE08');
        $this->addSql('ALTER TABLE facture_produit DROP FOREIGN KEY FK_61424D7EF347EFB');
        $this->addSql('DROP TABLE chargement');
        $this->addSql('DROP TABLE facture_produit');
        $this->addSql('ALTER TABLE client ADD pays VARCHAR(255) DEFAULT NULL, DROP telephone, CHANGE ville ville VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dette ADD paye TINYINT(1) NOT NULL, DROP statut');
        $this->addSql('DROP INDEX IDX_FE866410B8FBE502 ON facture');
        $this->addSql('ALTER TABLE facture ADD date_facture DATETIME NOT NULL, ADD montant_facture NUMERIC(10, 0) NOT NULL, DROP montant, DROP date, DROP quantite, DROP prix_unit, DROP total, DROP nom_client, DROP etat, CHANGE numero_facture numero_facture VARCHAR(255) NOT NULL, CHANGE chargement_id facture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664107F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('CREATE INDEX IDX_FE8664107F2DEE08 ON facture (facture_id)');
    }
}
