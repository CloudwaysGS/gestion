<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231124225641 extends AbstractMigration
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
        $this->addSql('ALTER TABLE detail DROP FOREIGN KEY FK_2E067F93CD11A2CF');
        $this->addSql('DROP INDEX IDX_2E067F93CD11A2CF ON detail');
        $this->addSql('ALTER TABLE detail DROP produits_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9F347EFB');
        $this->addSql('ALTER TABLE produit_detail DROP FOREIGN KEY FK_D954B4A9D8D003BB');
        $this->addSql('DROP TABLE produit_detail');
        $this->addSql('ALTER TABLE detail ADD produits_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE detail ADD CONSTRAINT FK_2E067F93CD11A2CF FOREIGN KEY (produits_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_2E067F93CD11A2CF ON detail (produits_id)');
    }
}
