<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout medecin_id à disponibilites (lien disponibilité -> médecin).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE disponibilites ADD medecin_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE disponibilites ADD CONSTRAINT FK_disponibilites_medecin FOREIGN KEY (medecin_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_disponibilites_medecin ON disponibilites (medecin_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE disponibilites DROP FOREIGN KEY FK_disponibilites_medecin');
        $this->addSql('DROP INDEX IDX_disponibilites_medecin ON disponibilites');
        $this->addSql('ALTER TABLE disponibilites DROP medecin_id');
    }
}
