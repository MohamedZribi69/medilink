<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les colonnes phone et sms_consent à la table user (schéma user-1.sql).
 */
final class Version20260225000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone and sms_consent columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD sms_consent TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP phone');
        $this->addSql('ALTER TABLE `user` DROP sms_consent');
    }
}
