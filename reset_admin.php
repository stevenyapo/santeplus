<?php
require_once 'config/database.php';

try {
    // Supprimer tous les administrateurs existants
    $pdo->exec("DELETE FROM administrateurs");
    
    // Créer le nouvel administrateur
    $nom = 'Admin';
    $prenom = 'System';
    $email = 'admin@santeplus.com';
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Afficher le hash pour vérification
    echo "Hash généré: " . $hashed_password . "\n\n";
    
    // Insérer le nouvel administrateur
    $stmt = $pdo->prepare("
        INSERT INTO administrateurs (nom, prenom, email, mot_de_passe)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$nom, $prenom, $email, $hashed_password]);
    
    echo "Administrateur réinitialisé avec succès!\n";
    echo "Email: admin@santeplus.com\n";
    echo "Mot de passe: admin123\n";
    
    // Vérifier que l'insertion a fonctionné
    $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "\nVérification de l'insertion:\n";
        echo "ID: " . $admin['id_admin'] . "\n";
        echo "Nom: " . $admin['nom'] . "\n";
        echo "Prénom: " . $admin['prenom'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Hash stocké: " . $admin['mot_de_passe'] . "\n";
        
        // Vérifier que le mot de passe fonctionne
        if (password_verify($password, $admin['mot_de_passe'])) {
            echo "\nVérification du mot de passe: SUCCÈS\n";
        } else {
            echo "\nVérification du mot de passe: ÉCHEC\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 