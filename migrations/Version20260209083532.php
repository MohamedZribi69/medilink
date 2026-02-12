<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209083532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3CCA0D7C6C6E55B5 ON categories_dons');
        $this->addSql('ALTER TABLE categories_dons DROP nom, DROP description, DROP icone, DROP couleur, DROP created_at');
        $this->addSql('ALTER TABLE dons DROP FOREIGN KEY dons_ibfk_1');
        $this->addSql('DROP INDEX IDX_E4F955FABCF5E72D ON dons');
        $this->addSql('ALTER TABLE dons DROP categorie_id, DROP article_description, DROP quantite, DROP unite, DROP details_supplementaires, DROP etat, DROP niveau_urgence, DROP statut, DROP date_expiration, DROP date_soumission');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categories_dons ADD nom VARCHAR(50) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD icone VARCHAR(50) DEFAULT NULL, ADD couleur VARCHAR(20) DEFAULT NULL, ADD created_at DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3CCA0D7C6C6E55B5 ON categories_dons (nom)');
        $this->addSql('ALTER TABLE dons ADD categorie_id INT NOT NULL, ADD article_description VARCHAR(255) NOT NULL, ADD quantite INT NOT NULL, ADD unite VARCHAR(20) DEFAULT NULL, ADD details_supplementaires LONGTEXT DEFAULT NULL, ADD etat VARCHAR(50) DEFAULT NULL, ADD niveau_urgence VARCHAR(20) DEFAULT NULL, ADD statut VARCHAR(20) DEFAULT NULL, ADD date_expiration DATE DEFAULT NULL, ADD date_soumission DATETIME NOT NULL');
        $this->addSql('ALTER TABLE dons ADD CONSTRAINT dons_ibfk_1 FOREIGN KEY (categorie_id) REFERENCES categories_dons (id)');
        $this->addSql('CREATE INDEX IDX_E4F955FABCF5E72D ON dons (categorie_id)');
    }
}
