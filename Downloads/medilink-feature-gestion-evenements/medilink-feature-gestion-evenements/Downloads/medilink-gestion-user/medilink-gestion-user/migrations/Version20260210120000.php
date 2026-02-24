<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Base evenements et participations déjà existantes (import SQL).
 * On ajoute uniquement user_id et commentaire à participations.
 */
final class Version20260210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout user_id et commentaire à la table participations (tables evenements/participations déjà créées par import).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participations ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE participations ADD commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_F55E19BB_A76ED395 ON participations (user_id)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_PARTICIPATIONS_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_PARTICIPATIONS_USER');
        $this->addSql('DROP INDEX IDX_F55E19BB_A76ED395 ON participations');
        $this->addSql('ALTER TABLE participations DROP user_id, DROP commentaire');
    }
}
