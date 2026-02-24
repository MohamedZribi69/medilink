<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224204643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dons DROP FOREIGN KEY dons_ibfk_1');
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY fk_om_medicament');
        $this->addSql('ALTER TABLE ordonnance_medicament DROP FOREIGN KEY fk_om_ordonnance');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY fk_rdv_disponibilite');
        $this->addSql('DROP TABLE categories_dons');
        $this->addSql('DROP TABLE disponibilite');
        $this->addSql('DROP TABLE dons');
        $this->addSql('DROP TABLE medicament');
        $this->addSql('DROP TABLE ordonnance');
        $this->addSql('DROP TABLE ordonnance_medicament');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('ALTER TABLE evenements CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY participations_ibfk_1');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY participations_ibfk_1');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_PARTICIPATIONS_USER');
        $this->addSql('ALTER TABLE participations CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E8FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenements (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX evenement_id ON participations');
        $this->addSql('CREATE INDEX IDX_FDC6C6E8FD02F13 ON participations (evenement_id)');
        $this->addSql('DROP INDEX idx_f55e19bb_a76ed395 ON participations');
        $this->addSql('CREATE INDEX IDX_FDC6C6E8A76ED395 ON participations (user_id)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT participations_ibfk_1 FOREIGN KEY (evenement_id) REFERENCES evenements (id)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_PARTICIPATIONS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories_dons (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, icone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'fa-box\' COLLATE `utf8mb4_general_ci`, couleur VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'#3498db\' COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX nom (nom), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE dons (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, article_description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, quantite INT NOT NULL, unite VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'unités\' COLLATE `utf8mb4_general_ci`, details_supplementaires TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, etat VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'Neuf / Non ouvert\' COLLATE `utf8mb4_general_ci`, niveau_urgence VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'Moyen\' COLLATE `utf8mb4_general_ci`, statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'en_attente\' COLLATE `utf8mb4_general_ci`, date_expiration DATE DEFAULT NULL, date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX categorie_id (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE medicament (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, quantite_stock INT DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ordonnance (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, instructions TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ordonnance_medicament (ordonnance_id INT NOT NULL, medicament_id INT NOT NULL, quantite INT DEFAULT 1 NOT NULL, INDEX fk_om_medicament (medicament_id), INDEX IDX_FE7DFAEE2BF23B8F (ordonnance_id), PRIMARY KEY(ordonnance_id, medicament_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, date_heure DATETIME NOT NULL, status VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, motif VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, disponibilite_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX disponibilite_id (disponibilite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE dons ADD CONSTRAINT dons_ibfk_1 FOREIGN KEY (categorie_id) REFERENCES categories_dons (id)');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT fk_om_medicament FOREIGN KEY (medicament_id) REFERENCES medicament (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE ordonnance_medicament ADD CONSTRAINT fk_om_ordonnance FOREIGN KEY (ordonnance_id) REFERENCES ordonnance (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT fk_rdv_disponibilite FOREIGN KEY (disponibilite_id) REFERENCES disponibilite (id)');
        $this->addSql('ALTER TABLE evenements CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E8FD02F13');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E8FD02F13');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E8A76ED395');
        $this->addSql('ALTER TABLE participations CHANGE statut statut VARCHAR(50) DEFAULT \'en_attente\', CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT participations_ibfk_1 FOREIGN KEY (evenement_id) REFERENCES evenements (id)');
        $this->addSql('DROP INDEX idx_fdc6c6e8a76ed395 ON participations');
        $this->addSql('CREATE INDEX IDX_F55E19BB_A76ED395 ON participations (user_id)');
        $this->addSql('DROP INDEX idx_fdc6c6e8fd02f13 ON participations');
        $this->addSql('CREATE INDEX evenement_id ON participations (evenement_id)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E8FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenements (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
