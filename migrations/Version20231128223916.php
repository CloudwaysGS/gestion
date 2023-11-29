<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231128223916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entree DROP FOREIGN KEY FK_598377A6D8D003BB');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D8D003BB');
        $this->addSql('ALTER TABLE detail_facture2 DROP FOREIGN KEY FK_F142C1D6CA7D27A1');
        $this->addSql('ALTER TABLE detail_facture2 DROP FOREIGN KEY FK_F142C1D6D8D003BB');
        $this->addSql('ALTER TABLE facture_detail DROP FOREIGN KEY FK_7B916D347F2DEE08');
        $this->addSql('ALTER TABLE facture_detail DROP FOREIGN KEY FK_7B916D34D8D003BB');
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9D8D003BB');
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9F347EFB');
        $this->addSql('DROP TABLE detail');
        $this->addSql('DROP TABLE detail_facture2');
        $this->addSql('DROP TABLE facture_detail');
        $this->addSql('DROP TABLE produit_detail');
        $this->addSql('DROP INDEX IDX_598377A6D8D003BB ON entree');
        $this->addSql('ALTER TABLE entree DROP detail_id');
        $this->addSql('DROP INDEX IDX_3C3FD3F2D8D003BB ON sortie');
        $this->addSql('ALTER TABLE sortie DROP detail_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, qt_stock DOUBLE PRECISION DEFAULT NULL, prix_unit DOUBLE PRECISION DEFAULT NULL, total DOUBLE PRECISION DEFAULT NULL, release_date DATETIME DEFAULT NULL, nom_produit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, stock_produit DOUBLE PRECISION DEFAULT NULL, nombre DOUBLE PRECISION DEFAULT NULL, prix_unit_detail DOUBLE PRECISION DEFAULT NULL, nombre_vendus DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_facture2 (detail_id INT NOT NULL, facture2_id INT NOT NULL, INDEX IDX_F142C1D6CA7D27A1 (facture2_id), INDEX IDX_F142C1D6D8D003BB (detail_id), PRIMARY KEY(detail_id, facture2_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE facture_detail (facture_id INT NOT NULL, detail_id INT NOT NULL, INDEX IDX_7B916D347F2DEE08 (facture_id), INDEX IDX_7B916D34D8D003BB (detail_id), PRIMARY KEY(facture_id, detail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE produit_detail (produit_id INT NOT NULL, detail_id INT NOT NULL, INDEX IDX_D954B4A9D8D003BB (detail_id), INDEX IDX_D954B4A9F347EFB (produit_id), PRIMARY KEY(produit_id, detail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE detail_facture2 ADD CONSTRAINT FK_F142C1D6CA7D27A1 FOREIGN KEY (facture2_id) REFERENCES facture2 (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE detail_facture2 ADD CONSTRAINT FK_F142C1D6D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_detail ADD CONSTRAINT FK_7B916D347F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_detail ADD CONSTRAINT FK_7B916D34D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_detail ADD CONSTRAINT FK_D954B4A9D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_detail ADD CONSTRAINT FK_D954B4A9F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entree ADD detail_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entree ADD CONSTRAINT FK_598377A6D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id)');
        $this->addSql('CREATE INDEX IDX_598377A6D8D003BB ON entree (detail_id)');
        $this->addSql('ALTER TABLE sortie ADD detail_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2D8D003BB ON sortie (detail_id)');
    }
}
