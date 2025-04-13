<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=santeplus", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "ALTER TABLE medecins ADD COLUMN cabinet VARCHAR(255) AFTER specialite";
    $pdo->exec($sql);
    echo "La colonne 'cabinet' a été ajoutée avec succès à la table 'medecins'.";
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 