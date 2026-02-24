<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table categories_dons.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS categories_dons (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(50) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            icone VARCHAR(50) DEFAULT \'fa-box\',
            couleur VARCHAR(20) DEFAULT \'#3498db\',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE categories_dons');
    }
}
