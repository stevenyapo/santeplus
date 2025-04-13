<?php
require_once 'config/database.php';

try {
    // Créer la table patients
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS patients (
            id_patient INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            telephone VARCHAR(15) NOT NULL,
            date_naissance DATE NOT NULL,
            sexe ENUM('M', 'F', 'Autre') NOT NULL,
            adresse TEXT NOT NULL,
            groupe_sanguin VARCHAR(5),
            allergies TEXT,
            maladies_chroniques TEXT,
            date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
            statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
            force_password_change TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table patients créée avec succès!\n";

    // Créer la table rendez_vous
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rendez_vous (
            id_rdv INT AUTO_INCREMENT PRIMARY KEY,
            id_patient INT NOT NULL,
            id_medecin INT NOT NULL,
            date_rdv DATETIME NOT NULL,
            motif TEXT NOT NULL,
            statut ENUM('en_attente', 'confirme', 'termine', 'annule') DEFAULT 'en_attente',
            notes TEXT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
            FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table rendez_vous créée avec succès!\n";

    // Créer la table urgences
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS urgences (
            id_urgence INT AUTO_INCREMENT PRIMARY KEY,
            id_patient INT NOT NULL,
            type_urgence ENUM('medicale', 'traumatique', 'psychologique') NOT NULL,
            niveau_urgence ENUM('faible', 'moyen', 'urgent') NOT NULL,
            description TEXT NOT NULL,
            statut ENUM('en_attente', 'en_cours', 'termine', 'annule') DEFAULT 'en_attente',
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_patient) REFERENCES patients(id_patient)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table urgences créée avec succès!\n";

    // Créer la table documents_medicaux
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents_medicaux (
            id_document INT AUTO_INCREMENT PRIMARY KEY,
            id_patient INT NOT NULL,
            id_medecin INT NOT NULL,
            type_document ENUM('ordonnance', 'certificat', 'resultat_analyse', 'imagerie', 'autre') NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description TEXT,
            chemin_fichier VARCHAR(255) NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
            FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table documents_medicaux créée avec succès!\n";

    // Créer la table demandes_documents
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS demandes_documents (
            id_demande INT AUTO_INCREMENT PRIMARY KEY,
            id_patient INT NOT NULL,
            id_medecin INT NOT NULL,
            type_document ENUM('ordonnance', 'certificat', 'resultat_analyse', 'imagerie', 'autre') NOT NULL,
            description TEXT,
            statut ENUM('en_attente', 'en_cours', 'termine', 'refuse') DEFAULT 'en_attente',
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
            FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table demandes_documents créée avec succès!\n";

    // Créer la table secrétaires
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS secretaires (
            id_secretaire INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            telephone VARCHAR(15) NOT NULL,
            date_embauche DATE NOT NULL,
            statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
            force_password_change TINYINT(1) DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table secretaires créée avec succès!\n";

    // Créer la table administrateurs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS administrateurs (
            id_admin INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            telephone VARCHAR(15) NOT NULL,
            statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
            force_password_change TINYINT(1) DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table administrateurs créée avec succès!\n";

    // Table des messages internes
    $sql = "CREATE TABLE IF NOT EXISTS messages_internes (
        id_message INT PRIMARY KEY AUTO_INCREMENT,
        expediteur_id INT NOT NULL,
        expediteur_role ENUM('medecin', 'secretaire', 'admin') NOT NULL,
        destinataire_id INT NOT NULL,
        destinataire_role ENUM('medecin', 'secretaire', 'admin') NOT NULL,
        sujet VARCHAR(255) NOT NULL,
        contenu TEXT NOT NULL,
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        lu BOOLEAN DEFAULT FALSE,
        supprime_expediteur BOOLEAN DEFAULT FALSE,
        supprime_destinataire BOOLEAN DEFAULT FALSE
    )";

    if ($pdo->query($sql)) {
        echo "Table messages_internes créée avec succès.<br>";
    } else {
        echo "Erreur lors de la création de la table messages_internes.<br>";
    }

    echo "\nToutes les tables ont été créées avec succès!\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 