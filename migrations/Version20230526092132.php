<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526092132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chargement DROP FOREIGN KEY FK_36328758A76ED395');
        $this->addSql('DROP INDEX IDX_36328758A76ED395 ON chargement');
        $this->addSql('ALTER TABLE chargement ADD connect VARCHAR(255) NOT NULL, DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chargement ADD user_id INT DEFAULT NULL, DROP connect');
        $this->addSql('ALTER TABLE chargement ADD CONSTRAINT FK_36328758A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_36328758A76ED395 ON chargement (user_id)');
    }
}
