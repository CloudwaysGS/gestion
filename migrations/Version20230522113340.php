<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522113340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE produit_detail (produit_id INT NOT NULL, detail_id INT NOT NULL, INDEX IDX_D954B4A9F347EFB (produit_id), INDEX IDX_D954B4A9D8D003BB (detail_id), PRIMARY KEY(produit_id, detail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE produit_detail ADD CONSTRAINT FK_D954B4A9F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_detail ADD CONSTRAINT FK_D954B4A9D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture ADD numero_facture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9F347EFB');
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9D8D003BB');
        $this->addSql('DROP TABLE produit_detail');
        $this->addSql('ALTER TABLE facture DROP numero_facture');
    }
}
