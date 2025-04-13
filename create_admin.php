<?php
require_once 'config/database.php';

try {
    // Supprimer l'administrateur existant
    $pdo->exec("DELETE FROM administrateurs");
    
    // Créer le nouvel administrateur
    $nom = 'Admin';
    $prenom = 'System';
    $email = 'admin@santeplus.com';
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO administrateurs (nom, prenom, email, mot_de_passe)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$nom, $prenom, $email, $hashed_password]);
    
    echo "Administrateur créé avec succès!\n";
    echo "Email: admin@santeplus.com\n";
    echo "Mot de passe: admin123\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 