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
        // No-op: cette migration supprimait des colonnes nécessaires (categories_dons, dons).
        // Le schéma actuel est conservé.
    }

    public function down(Schema $schema): void
    {
        // No-op
    }
}
