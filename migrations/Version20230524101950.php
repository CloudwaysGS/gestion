<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524101950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_detail DROP FOREIGN KEY FK_7B916D347F2DEE08');
        $this->addSql('ALTER TABLE facture_detail DROP FOREIGN KEY FK_7B916D34D8D003BB');
        $this->addSql('DROP TABLE facture_detail');
        $this->addSql('ALTER TABLE detail ADD facture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE detail ADD CONSTRAINT FK_2E067F937F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('CREATE INDEX IDX_2E067F937F2DEE08 ON detail (facture_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture_detail (facture_id INT NOT NULL, detail_id INT NOT NULL, INDEX IDX_7B916D347F2DEE08 (facture_id), INDEX IDX_7B916D34D8D003BB (detail_id), PRIMARY KEY(facture_id, detail_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE facture_detail ADD CONSTRAINT FK_7B916D347F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_detail ADD CONSTRAINT FK_7B916D34D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE detail DROP FOREIGN KEY FK_2E067F937F2DEE08');
        $this->addSql('DROP INDEX IDX_2E067F937F2DEE08 ON detail');
        $this->addSql('ALTER TABLE detail DROP facture_id');
    }
}
