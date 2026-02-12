<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corriger les FK de ordonnance_medicament : référencer ordonnance et medicament (singulier).';
    }

    public function up(Schema $schema): void
    {
        // Corriger : la FK ordonnance_id doit référencer la table "ordonnance" (singulier), pas "ordonnances"
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_ordonnance');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY FK_ord_med_ordonnance');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT FK_ord_med_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnances (id) ON DELETE CASCADE');
    }
}
