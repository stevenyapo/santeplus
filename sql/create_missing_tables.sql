-- Création de la table patients
CREATE TABLE IF NOT EXISTS `patients` (
    `id_patient` int(11) NOT NULL AUTO_INCREMENT,
    `nom` varchar(50) NOT NULL,
    `prenom` varchar(50) NOT NULL,
    `date_naissance` date NOT NULL,
    `sexe` enum('M','F') NOT NULL,
    `adresse` varchar(255) NOT NULL,
    `telephone` varchar(20) NOT NULL,
    `email` varchar(100) NOT NULL,
    `mot_de_passe` varchar(255) NOT NULL,
    `groupe_sanguin` varchar(5),
    `antecedents` text,
    `allergies` text,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `derniere_connexion` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_patient`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table rendez_vous
CREATE TABLE IF NOT EXISTS `rendez_vous` (
    `id_rdv` int(11) NOT NULL AUTO_INCREMENT,
    `id_patient` int(11) NOT NULL,
    `id_medecin` int(11) NOT NULL,
    `date_rdv` datetime NOT NULL,
    `motif` varchar(255) NOT NULL,
    `statut` enum('en_attente','confirme','annule') NOT NULL DEFAULT 'en_attente',
    `notes` text,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rdv`),
    KEY `id_patient` (`id_patient`),
    KEY `id_medecin` (`id_medecin`),
    CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`),
    CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `medecins` (`id_medecin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table urgences
CREATE TABLE IF NOT EXISTS `urgences` (
    `id_urgence` int(11) NOT NULL AUTO_INCREMENT,
    `id_patient` int(11) NOT NULL,
    `id_medecin` int(11),
    `description` text NOT NULL,
    `priorite` enum('basse','moyenne','haute','critique') NOT NULL,
    `statut` enum('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_traitement` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_urgence`),
    KEY `id_patient` (`id_patient`),
    KEY `id_medecin` (`id_medecin`),
    CONSTRAINT `urgences_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`),
    CONSTRAINT `urgences_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `medecins` (`id_medecin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 