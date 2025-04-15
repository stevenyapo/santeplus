<?php
require_once '../config/database.php';
require_once '../classes/Logger.php';

class BackupManager {
    private $db;
    private $logger;
    private $backupDir;
    private $config;

    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->backupDir = __DIR__ . '/../backups';
        $this->config = require_once '../config/backup.php';
        
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function runBackup() {
        try {
            $this->logger->info("Début du backup");
            
            // Créer le nom du fichier de backup
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            // Récupérer la liste des tables
            $tables = $this->getTables();
            
            // Créer le fichier de backup
            $handle = fopen($filepath, 'w');
            
            // Écrire l'en-tête
            fwrite($handle, "-- Backup de la base de données\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
            
            // Sauvegarder chaque table
            foreach ($tables as $table) {
                $this->backupTable($table, $handle);
            }
            
            fclose($handle);
            
            // Compresser le fichier
            $this->compressBackup($filepath);
            
            // Supprimer les anciens backups
            $this->cleanOldBackups();
            
            $this->logger->info("Backup terminé avec succès", ['file' => $filename]);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du backup", ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function getTables() {
        $stmt = $this->db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function backupTable($table, $handle) {
        // Écrire la structure de la table
        $stmt = $this->db->query("SHOW CREATE TABLE `$table`");
        $create = $stmt->fetch(PDO::FETCH_NUM);
        
        fwrite($handle, "\n-- Structure de la table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $create[1] . ";\n\n");
        
        // Écrire les données
        $stmt = $this->db->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            fwrite($handle, "-- Données de la table `$table`\n");
            
            foreach ($rows as $row) {
                $values = array_map(function($value) {
                    return $value === null ? 'NULL' : $this->db->quote($value);
                }, $row);
                
                fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
            }
        }
    }

    private function compressBackup($filepath) {
        $zip = new ZipArchive();
        $zipname = $filepath . '.zip';
        
        if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($filepath, basename($filepath));
            $zip->close();
            
            // Supprimer le fichier SQL original
            unlink($filepath);
        }
    }

    private function cleanOldBackups() {
        $files = glob($this->backupDir . '/*.zip');
        $maxBackups = $this->config['max_backups'] ?? 10;
        
        if (count($files) > $maxBackups) {
            // Trier les fichiers par date de modification
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Supprimer les plus anciens
            $toDelete = count($files) - $maxBackups;
            for ($i = 0; $i < $toDelete; $i++) {
                unlink($files[$i]);
            }
        }
    }
}

// Exécution du backup
try {
    $logger = new Logger($db);
    $backupManager = new BackupManager($db, $logger);
    $backupManager->runBackup();
} catch (Exception $e) {
    error_log("Erreur lors de l'exécution du backup: " . $e->getMessage());
} 