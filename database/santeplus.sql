-- Création de la base de données
CREATE DATABASE IF NOT EXISTS santeplus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE santeplus;

-- Table des patients
CREATE TABLE IF NOT EXISTS patients (
    id_patient INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(15) NOT NULL,
    date_naissance DATE NOT NULL,
    sexe ENUM('M', 'F', 'Autre') NOT NULL,
    adresse TEXT NOT NULL,
    groupe_sanguin VARCHAR(3),
    allergies TEXT,
    maladies_chroniques TEXT,
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Table des médecins
CREATE TABLE IF NOT EXISTS medecins (
    id_medecin INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(15) NOT NULL,
    specialite VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    disponibilites JSON,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Table des rendez-vous
CREATE TABLE IF NOT EXISTS rendezvous (
    id_rdv INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    date_heure DATETIME NOT NULL,
    motif TEXT NOT NULL,
    statut ENUM('en_attente', 'confirme', 'annule', 'termine') DEFAULT 'en_attente',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin),
    INDEX idx_date_heure (date_heure),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- Table des rapports
CREATE TABLE IF NOT EXISTS rapports (
    id_rapport INT PRIMARY KEY AUTO_INCREMENT,
    id_rdv INT NOT NULL,
    id_medecin INT NOT NULL,
    description TEXT NOT NULL,
    prescriptions TEXT,
    date_rapport DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rdv) REFERENCES rendezvous(id_rdv),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin),
    INDEX idx_date_rapport (date_rapport)
) ENGINE=InnoDB;

-- Table des secrétaires
CREATE TABLE IF NOT EXISTS secretaire (
    id_secretaire INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(15) NOT NULL,
    adresse TEXT NOT NULL,
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Table des urgences
CREATE TABLE IF NOT EXISTS urgences (
    id_urgence INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    motif TEXT NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'en_cours', 'termine', 'annule') DEFAULT 'en_attente',
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
    INDEX idx_date_demande (date_demande),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- Table des messages
CREATE TABLE IF NOT EXISTS messages (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    message TEXT NOT NULL,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT FALSE,
    INDEX idx_date_envoi (date_envoi)
) ENGINE=InnoDB;

-- Table des documents médicaux
CREATE TABLE IF NOT EXISTS documents_medicaux (
    id_document INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    type_document VARCHAR(50) NOT NULL,
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin),
    INDEX idx_date_upload (date_upload)
) ENGINE=InnoDB;

-- Table des téléconsultations
CREATE TABLE IF NOT EXISTS teleconsultations (
    id_teleconsultation INT PRIMARY KEY AUTO_INCREMENT,
    id_rdv INT NOT NULL,
    lien_reunion VARCHAR(255),
    statut ENUM('planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'planifiee',
    date_debut DATETIME,
    date_fin DATETIME,
    FOREIGN KEY (id_rdv) REFERENCES rendezvous(id_rdv),
    INDEX idx_date_debut (date_debut)
) ENGINE=InnoDB;

-- Table des suivis de traitement
CREATE TABLE IF NOT EXISTS suivis_traitement (
    id_suivi INT PRIMARY KEY AUTO_INCREMENT,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    traitement TEXT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    statut ENUM('en_cours', 'termine', 'interrompu') DEFAULT 'en_cours',
    commentaires TEXT,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin),
    INDEX idx_date_debut (date_debut)
) ENGINE=InnoDB; 