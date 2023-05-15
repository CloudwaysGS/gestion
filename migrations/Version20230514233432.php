<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230514233432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail ADD produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE detail ADD CONSTRAINT FK_2E067F93F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_2E067F93F347EFB ON detail (produit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail DROP FOREIGN KEY FK_2E067F93F347EFB');
        $this->addSql('DROP INDEX IDX_2E067F93F347EFB ON detail');
        $this->addSql('ALTER TABLE detail DROP produit_id');
    }
}
