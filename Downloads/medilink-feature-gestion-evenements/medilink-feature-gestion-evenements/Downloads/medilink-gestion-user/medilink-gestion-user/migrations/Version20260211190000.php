<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter medecin_id et patient_id à ordonnance (option 1 - lien médecin/patient).';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();
        if (!$sm->tablesExist(['ordonnance'])) {
            return;
        }
        $cols = $sm->listTableColumns('ordonnance');
        $colNames = array_map(static fn ($c) => strtolower($c->getName()), $cols);
        $hasMedecinId = in_array('medecin_id', $colNames, true);
        $hasPatientId = in_array('patient_id', $colNames, true);

        if (!$hasMedecinId) {
            $this->addSql('ALTER TABLE ordonnance ADD medecin_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_ordonnance_medecin ON ordonnance (medecin_id)');
            $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_ordonnance_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE');
        }
        if (!$hasPatientId) {
            $this->addSql('ALTER TABLE ordonnance ADD patient_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_ordonnance_patient ON ordonnance (patient_id)');
            $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_ordonnance_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_ordonnance_medecin');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_ordonnance_patient');
        $this->addSql('DROP INDEX IDX_ordonnance_medecin ON ordonnance');
        $this->addSql('DROP INDEX IDX_ordonnance_patient ON ordonnance');
        $this->addSql('ALTER TABLE ordonnance DROP medecin_id, DROP patient_id');
    }
}
