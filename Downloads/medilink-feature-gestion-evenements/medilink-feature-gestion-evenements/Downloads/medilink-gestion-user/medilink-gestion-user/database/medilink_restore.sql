-- Supprimer complètement la base medilink et la recréer avec ce schéma
-- Exécuter ce fichier dans phpMyAdmin (onglet SQL) en étant connecté au serveur MySQL (sans sélectionner de base).

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DROP DATABASE IF EXISTS `medilink`;
CREATE DATABASE `medilink` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `medilink`;

-- --------------------------------------------------------
-- Structure de la table `categories_dons`
-- --------------------------------------------------------
CREATE TABLE `categories_dons` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT 'fa-box',
  `couleur` varchar(20) DEFAULT '#3498db',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `disponibilite`
-- --------------------------------------------------------
CREATE TABLE `disponibilite` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `doctrine_migration_versions`
-- --------------------------------------------------------
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `dons`
-- --------------------------------------------------------
CREATE TABLE `dons` (
  `id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `article_description` varchar(255) NOT NULL,
  `quantite` int(11) NOT NULL,
  `unite` varchar(20) DEFAULT 'unités',
  `details_supplementaires` text DEFAULT NULL,
  `etat` varchar(50) DEFAULT 'Neuf / Non ouvert',
  `niveau_urgence` varchar(20) DEFAULT 'Moyen',
  `statut` varchar(20) DEFAULT 'en_attente',
  `date_expiration` date DEFAULT NULL,
  `date_soumission` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `evenements`
-- --------------------------------------------------------
CREATE TABLE `evenements` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_evenement` date NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `medicament`
-- --------------------------------------------------------
CREATE TABLE `medicament` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantite_stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `ordonnance`
-- --------------------------------------------------------
CREATE TABLE `ordonnance` (
  `id` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `ordonnance_medicament`
-- --------------------------------------------------------
CREATE TABLE `ordonnance_medicament` (
  `ordonnance_id` int(11) NOT NULL,
  `medicament_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `user` (avant participations pour FK)
-- --------------------------------------------------------
CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `participations`
-- --------------------------------------------------------
CREATE TABLE `participations` (
  `id` int(11) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `statut` varchar(50) DEFAULT 'en_attente',
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `commentaire` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Structure de la table `rendez_vous`
-- --------------------------------------------------------
CREATE TABLE `rendez_vous` (
  `id` int(11) NOT NULL,
  `date_heure` datetime NOT NULL,
  `status` varchar(30) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `disponibilite_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Données
-- --------------------------------------------------------
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260207123335', '2026-02-07 13:33:47', 166),
('DoctrineMigrations\\Version20260210120000', '2026-02-10 17:52:14', 108),
('DoctrineMigrations\\Version20260210140000', '2026-02-10 19:02:58', 13);

INSERT INTO `evenements` (`id`, `titre`, `description`, `date_evenement`, `lieu`, `type`, `created_at`, `photo`) VALUES
(1, 'ASLEMA', 'bien', '0026-05-12', 'ariana', 'cult', '2026-02-10 17:39:40', NULL),
(2, 'cc', 'hii', '2026-07-13', 'tunis', 'lili', '2026-02-10 17:53:57', NULL),
(3, 'FDFBG', 'RGTSRTHBRYTHB', '2027-03-05', 'fnk', 'hfvhbfd', '2026-02-10 19:06:00', 'Home-Care-Flyer-Templates-Preview-Professional-Medical-and-Nursing-Posters-698b8199631b36.91981801.jpg');

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `full_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'zeribimohamed69@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$13$/f73a1fRyUoqcJr8Uj.JyOFGsUWLwpoxDNEqdjiyzfR2Bup7VE/ee', 'mohamed zribi', 'ACTIVE', '2026-02-07 13:58:29', NULL),
(2, 'dhia.taamouli@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$13$uoLldmMgp4Qgbokdj61BuOpONZmfZ33I1gyDAJlNvqLwRfewiDqsG', 'dhia taamouli', 'ACTIVE', '2026-02-07 14:50:49', NULL),
(3, 'seif.bensalem@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$13$Jut/yIVa1eLCwJQQZYQghOs.bODtz6aICFsFXeix.BRgt4o1G0FZe', 'seif ben salem', 'ACTIVE', '2026-02-07 15:04:12', NULL),
(4, 'slim@gmail.com', '[\"ROLE_USER\"]', '$2y$13$nt6ntkLLzqcFONfkQXUCFedzYUiWXuule9m/3zqnKz2HlQsVj7Oke', 'slim ammar', 'ACTIVE', '2026-02-10 18:04:09', NULL);

INSERT INTO `participations` (`id`, `evenement_id`, `statut`, `date_inscription`, `user_id`, `commentaire`) VALUES
(2, 2, 'en_attente', '2026-02-10 17:57:04', 4, 'kufv'),
(3, 3, 'en_attente', '2026-02-10 19:12:24', 4, 'je souhaite participer à votre evenement');

-- --------------------------------------------------------
-- Index
-- --------------------------------------------------------
ALTER TABLE `categories_dons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

ALTER TABLE `disponibilite`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

ALTER TABLE `dons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`categorie_id`);

ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `medicament`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ordonnance`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ordonnance_medicament`
  ADD PRIMARY KEY (`ordonnance_id`,`medicament_id`),
  ADD KEY `fk_om_medicament` (`medicament_id`);

ALTER TABLE `participations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evenement_id` (`evenement_id`),
  ADD KEY `IDX_F55E19BB_A76ED395` (`user_id`);

ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `disponibilite_id` (`disponibilite_id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`);

-- --------------------------------------------------------
-- AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `categories_dons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `disponibilite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `evenements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `medicament`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ordonnance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `participations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `rendez_vous`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- --------------------------------------------------------
-- Contraintes
-- --------------------------------------------------------
ALTER TABLE `dons`
  ADD CONSTRAINT `dons_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories_dons` (`id`);

ALTER TABLE `ordonnance_medicament`
  ADD CONSTRAINT `fk_om_medicament` FOREIGN KEY (`medicament_id`) REFERENCES `medicament` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_om_ordonnance` FOREIGN KEY (`ordonnance_id`) REFERENCES `ordonnance` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `participations`
  ADD CONSTRAINT `FK_PARTICIPATIONS_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`);

ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `fk_rdv_disponibilite` FOREIGN KEY (`disponibilite_id`) REFERENCES `disponibilite` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
