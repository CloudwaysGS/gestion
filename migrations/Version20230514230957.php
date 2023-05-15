<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230514230957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD facture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC277F2DEE08 ON produit (facture_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277F2DEE08');
        $this->addSql('DROP INDEX IDX_29A5EC277F2DEE08 ON produit');
        $this->addSql('ALTER TABLE produit DROP facture_id');
    }
}
