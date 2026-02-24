<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout patient_id à rendez_vous (réservation par le patient).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rendez_vous ADD patient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_rendez_vous_patient FOREIGN KEY (patient_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_rendez_vous_patient ON rendez_vous (patient_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_rendez_vous_patient');
        $this->addSql('DROP INDEX IDX_rendez_vous_patient ON rendez_vous');
        $this->addSql('ALTER TABLE rendez_vous DROP patient_id');
    }
}
