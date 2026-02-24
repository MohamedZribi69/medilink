<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renommer tables medicaments -> medicament, ordonnances -> ordonnance.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        $tables = array_map('strtolower', $sm->listTableNames());
        $hasMedicaments = in_array('medicaments', $tables, true);
        $hasMedicament = in_array('medicament', $tables, true);
        $hasOrdonnances = in_array('ordonnances', $tables, true);
        $hasOrdonnance = in_array('ordonnance', $tables, true);

        // Si les tables au singulier existent déjà, ne rien faire
        if ($hasMedicament && $hasOrdonnance) {
            return;
        }

        // Renommer seulement si les tables au pluriel existent et les singulières non
        if ($hasMedicaments && !$hasMedicament && $hasOrdonnances && !$hasOrdonnance) {
            $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_ordonnance');
            $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_medicament');
            $this->addSql('ALTER TABLE ordonnances DROP FOREIGN KEY FK_ordonnances_medecin');
            $this->addSql('ALTER TABLE ordonnances DROP FOREIGN KEY FK_ordonnances_patient');
            $this->addSql('RENAME TABLE medicaments TO medicament, ordonnances TO ordonnance');
            $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_ordonnance_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_ordonnance_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_medicament FOREIGN KEY (medicament_id) REFERENCES medicament (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_ordonnance');
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_medicament');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_ordonnance_medecin');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_ordonnance_patient');
        $this->addSql('RENAME TABLE medicament TO medicaments, ordonnance TO ordonnances');
        $this->addSql('ALTER TABLE ordonnances ADD CONSTRAINT FK_ordonnances_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnances ADD CONSTRAINT FK_ordonnances_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnances (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_medicament FOREIGN KEY (medicament_id) REFERENCES medicaments (id) ON DELETE CASCADE');
    }
}
