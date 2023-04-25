<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230424212122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_fournisseur ADD fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dette_fournisseur ADD CONSTRAINT FK_F5D38592670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('CREATE INDEX IDX_F5D38592670C757F ON dette_fournisseur (fournisseur_id)');
        $this->addSql('ALTER TABLE payoff_supplier ADD dette_fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payoff_supplier ADD CONSTRAINT FK_908127FDC1F8463B FOREIGN KEY (dette_fournisseur_id) REFERENCES dette_fournisseur (id)');
        $this->addSql('CREATE INDEX IDX_908127FDC1F8463B ON payoff_supplier (dette_fournisseur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dette_fournisseur DROP FOREIGN KEY FK_F5D38592670C757F');
        $this->addSql('DROP INDEX IDX_F5D38592670C757F ON dette_fournisseur');
        $this->addSql('ALTER TABLE dette_fournisseur DROP fournisseur_id');
        $this->addSql('ALTER TABLE payoff_supplier DROP FOREIGN KEY FK_908127FDC1F8463B');
        $this->addSql('DROP INDEX IDX_908127FDC1F8463B ON payoff_supplier');
        $this->addSql('ALTER TABLE payoff_supplier DROP dette_fournisseur_id');
    }
}
