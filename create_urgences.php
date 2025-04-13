<?php
require_once 'config/database.php';

try {
    // Supprimer la table si elle existe
    $pdo->exec("DROP TABLE IF EXISTS urgences");
    
    // Créer la table urgences
    $pdo->exec("
        CREATE TABLE urgences (
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
    echo "Table urgences recréée avec succès!\n";

    // Insérer quelques données de test
    $stmt = $pdo->prepare("
        INSERT INTO urgences (id_patient, type_urgence, niveau_urgence, description)
        VALUES (?, ?, ?, ?)
    ");

    // Récupérer un ID de patient existant
    $patient_id = $pdo->query("SELECT id_patient FROM patients LIMIT 1")->fetchColumn();

    if ($patient_id) {
        $urgences_test = [
            [
                'type' => 'medicale',
                'niveau' => 'urgent',
                'description' => 'Douleurs thoraciques intenses'
            ],
            [
                'type' => 'traumatique',
                'niveau' => 'moyen',
                'description' => 'Entorse à la cheville'
            ],
            [
                'type' => 'psychologique',
                'niveau' => 'faible',
                'description' => 'Anxiété légère'
            ]
        ];

        foreach ($urgences_test as $urgence) {
            $stmt->execute([
                $patient_id,
                $urgence['type'],
                $urgence['niveau'],
                $urgence['description']
            ]);
        }

        echo "Données de test insérées avec succès!\n";
    } else {
        echo "Attention: Aucun patient trouvé pour insérer les données de test.\n";
    }

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 