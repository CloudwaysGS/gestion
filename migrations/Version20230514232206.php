<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230514232206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture_produit (facture_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_61424D7E7F2DEE08 (facture_id), INDEX IDX_61424D7EF347EFB (produit_id), PRIMARY KEY(facture_id, produit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture_produit ADD CONSTRAINT FK_61424D7E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_produit ADD CONSTRAINT FK_61424D7EF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277F2DEE08');
        $this->addSql('DROP INDEX IDX_29A5EC277F2DEE08 ON produit');
        $this->addSql('ALTER TABLE produit DROP facture_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_produit DROP FOREIGN KEY FK_61424D7E7F2DEE08');
        $this->addSql('ALTER TABLE facture_produit DROP FOREIGN KEY FK_61424D7EF347EFB');
        $this->addSql('DROP TABLE facture_produit');
        $this->addSql('ALTER TABLE produit ADD facture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC277F2DEE08 ON produit (facture_id)');
    }
}
