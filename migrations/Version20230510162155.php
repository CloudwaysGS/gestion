<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230510162155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail ADD prix_unit DOUBLE PRECISION DEFAULT NULL, ADD total DOUBLE PRECISION DEFAULT NULL, DROP prix, DROP montant');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail ADD prix DOUBLE PRECISION DEFAULT NULL, ADD montant DOUBLE PRECISION DEFAULT NULL, DROP prix_unit, DROP total');
    }
}
