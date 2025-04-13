<?php
require_once 'config/database.php';

try {
    // Créer la table medecins
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS medecins (
            id_medecin INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            specialite VARCHAR(100) NOT NULL,
            telephone VARCHAR(20),
            adresse TEXT,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Table medecins créée avec succès!\n";

    // Créer un médecin par défaut pour tester
    $nom = 'Dupont';
    $prenom = 'Jean';
    $email = 'dr.dupont@santeplus.com';
    $password = 'medecin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $specialite = 'Médecin généraliste';
    $telephone = '0123456789';
    $adresse = '123 rue de la Santé, 75000 Paris';

    $stmt = $pdo->prepare("
        INSERT INTO medecins (nom, prenom, email, mot_de_passe, specialite, telephone, adresse)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$nom, $prenom, $email, $hashed_password, $specialite, $telephone, $adresse]);
    
    echo "\nMédecin par défaut créé avec succès!\n";
    echo "Email: dr.dupont@santeplus.com\n";
    echo "Mot de passe: medecin123\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 