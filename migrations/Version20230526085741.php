<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526085741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FE866410A76ED395 ON facture (user_id)');
        $this->addSql('ALTER TABLE facture2 ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture2 ADD CONSTRAINT FK_79C280DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_79C280DA76ED395 ON facture2 (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410A76ED395');
        $this->addSql('DROP INDEX IDX_FE866410A76ED395 ON facture');
        $this->addSql('ALTER TABLE facture DROP user_id');
        $this->addSql('ALTER TABLE facture2 DROP FOREIGN KEY FK_79C280DA76ED395');
        $this->addSql('DROP INDEX IDX_79C280DA76ED395 ON facture2');
        $this->addSql('ALTER TABLE facture2 DROP user_id');
    }
}
