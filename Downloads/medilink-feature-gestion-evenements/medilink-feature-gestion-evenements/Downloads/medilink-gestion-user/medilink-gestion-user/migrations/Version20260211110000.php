<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table rendez_vous.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS rendez_vous (
            id INT AUTO_INCREMENT NOT NULL,
            patient_id INT NOT NULL,
            medecin_id INT NOT NULL,
            date_heure DATETIME NOT NULL,
            duree_minutes SMALLINT DEFAULT 30 NOT NULL,
            statut VARCHAR(20) NOT NULL,
            motifs LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            INDEX IDX_rendez_vous_patient (patient_id),
            INDEX IDX_rendez_vous_medecin (medecin_id),
            CONSTRAINT FK_rendez_vous_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE,
            CONSTRAINT FK_rendez_vous_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rendez_vous');
    }
}
