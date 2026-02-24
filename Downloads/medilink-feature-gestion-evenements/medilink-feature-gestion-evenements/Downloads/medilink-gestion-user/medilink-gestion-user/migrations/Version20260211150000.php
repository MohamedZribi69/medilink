<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables medicaments, ordonnances, ordonnance_medicament.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS medicaments (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantite_stock INT DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS ordonnances (id INT AUTO_INCREMENT NOT NULL, medecin_id INT NOT NULL, patient_id INT NOT NULL, date_creation DATETIME NOT NULL, instructions LONGTEXT DEFAULT NULL, INDEX IDX_ordonnances_medecin (medecin_id), INDEX IDX_ordonnances_patient (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS ordonnance_medicament (ordonnance_id INT NOT NULL, medicament_id INT NOT NULL, quantite INT DEFAULT 1 NOT NULL, INDEX IDX_ord_med_ordonnance (ordonnance_id), INDEX IDX_ord_med_medicament (medicament_id), PRIMARY KEY(ordonnance_id, medicament_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ordonnances ADD CONSTRAINT FK_ordonnances_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnances ADD CONSTRAINT FK_ordonnances_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnances (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_medicament FOREIGN KEY (medicament_id) REFERENCES medicaments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_ordonnance');
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_medicament');
        $this->addSql('ALTER TABLE ordonnances DROP FOREIGN KEY FK_ordonnances_medecin');
        $this->addSql('ALTER TABLE ordonnances DROP FOREIGN KEY FK_ordonnances_patient');
        $this->addSql('DROP TABLE ordonnance_medicament');
        $this->addSql('DROP TABLE ordonnances');
        $this->addSql('DROP TABLE medicaments');
    }
}
