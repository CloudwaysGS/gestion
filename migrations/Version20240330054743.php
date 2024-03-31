<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240330054743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_depot ADD produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie_depot ADD CONSTRAINT FK_DE9A2EB8F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('CREATE INDEX IDX_DE9A2EB8F347EFB ON sortie_depot (produit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sortie_depot DROP FOREIGN KEY FK_DE9A2EB8F347EFB');
        $this->addSql('DROP INDEX IDX_DE9A2EB8F347EFB ON sortie_depot');
        $this->addSql('ALTER TABLE sortie_depot DROP produit_id');
    }
}
