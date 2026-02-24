<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout colonne photo à la table evenements.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenements ADD photo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenements DROP photo');
    }
}
