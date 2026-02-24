<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refonte rendez-vous: table disponibilites + rendez_vous avec relation OneToOne.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS rendez_vous');

        $this->addSql('CREATE TABLE IF NOT EXISTS disponibilites (
            id INT AUTO_INCREMENT NOT NULL,
            date DATE NOT NULL,
            heure_debut TIME NOT NULL,
            heure_fin TIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('CREATE TABLE rendez_vous (
            id INT AUTO_INCREMENT NOT NULL,
            disponibilite_id INT NOT NULL,
            date_heure DATETIME NOT NULL,
            statut VARCHAR(20) NOT NULL,
            motif LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_rendez_vous_disponibilite (disponibilite_id),
            CONSTRAINT FK_rendez_vous_disponibilite FOREIGN KEY (disponibilite_id) REFERENCES disponibilites (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE disponibilites');

        $this->addSql('CREATE TABLE rendez_vous (
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
}
