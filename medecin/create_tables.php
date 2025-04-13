<?php
// Vérifier si les tables existent et les créer si nécessaire
try {
    // Vérifier si la table conversations existe
    $result = $pdo->query("SHOW TABLES LIKE 'conversations'");
    if ($result->rowCount() == 0) {
        // Créer les tables si elles n'existent pas
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `conversations` (
                `id_conversation` int(11) NOT NULL AUTO_INCREMENT,
                `id_medecin` int(11) NOT NULL,
                `id_admin` int(11) NOT NULL,
                `dernier_message` text DEFAULT NULL,
                `date_dernier_message` datetime DEFAULT NULL,
                `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_conversation`),
                KEY `id_medecin` (`id_medecin`),
                KEY `id_admin` (`id_admin`),
                CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`id_medecin`) REFERENCES `medecins` (`id_medecin`) ON DELETE CASCADE,
                CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `administrateurs` (`id_admin`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `messages` (
                `id_message` int(11) NOT NULL AUTO_INCREMENT,
                `id_conversation` int(11) NOT NULL,
                `id_expediteur` int(11) NOT NULL,
                `message` text NOT NULL,
                `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
                `lu` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_message`),
                KEY `id_conversation` (`id_conversation`),
                CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_conversation`) REFERENCES `conversations` (`id_conversation`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) {
    throw new Exception("Une erreur est survenue lors de la création des tables : " . $e->getMessage());
}
?> 