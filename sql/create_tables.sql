-- Création de la table secretaire
CREATE TABLE IF NOT EXISTS `secretaire` (
    `id_secretaire` int(11) NOT NULL AUTO_INCREMENT,
    `nom` varchar(50) NOT NULL,
    `prenom` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `mot_de_passe` varchar(255) NOT NULL,
    `telephone` varchar(20) DEFAULT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `derniere_connexion` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_secretaire`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table medecins
CREATE TABLE IF NOT EXISTS `medecins` (
    `id_medecin` int(11) NOT NULL AUTO_INCREMENT,
    `nom` varchar(50) NOT NULL,
    `prenom` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `mot_de_passe` varchar(255) NOT NULL,
    `specialite` varchar(100) DEFAULT NULL,
    `telephone` varchar(20) DEFAULT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `derniere_connexion` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_medecin`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table rapports_hemodialyse
CREATE TABLE IF NOT EXISTS `rapports_hemodialyse` (
    `id_rapport` int(11) NOT NULL AUTO_INCREMENT,
    `id_medecin` int(11) NOT NULL,
    `id_patient` int(11) NOT NULL,
    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` timestamp NULL DEFAULT NULL,
    `contenu` text NOT NULL,
    `statut` enum('brouillon','finalise') NOT NULL DEFAULT 'brouillon',
    PRIMARY KEY (`id_rapport`),
    KEY `id_medecin` (`id_medecin`),
    KEY `id_patient` (`id_patient`),
    CONSTRAINT `rapports_hemodialyse_ibfk_1` FOREIGN KEY (`id_medecin`) REFERENCES `medecins` (`id_medecin`),
    CONSTRAINT `rapports_hemodialyse_ibfk_2` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 