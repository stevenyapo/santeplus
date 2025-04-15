<?php
require_once '../config/database.php';
require_once '../classes/Logger.php';

class DeployManager {
    private $db;
    private $logger;
    private $config;
    private $deployDir;

    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->config = require_once '../config/deploy.php';
        $this->deployDir = __DIR__ . '/../deploy';
        
        if (!file_exists($this->deployDir)) {
            mkdir($this->deployDir, 0755, true);
        }
    }

    public function deploy() {
        try {
            $this->logger->info("Début du déploiement");
            
            // 1. Vérifier les prérequis
            $this->checkPrerequisites();
            
            // 2. Créer un backup
            $this->createBackup();
            
            // 3. Mettre à jour les fichiers
            $this->updateFiles();
            
            // 4. Mettre à jour la base de données
            $this->updateDatabase();
            
            // 5. Vérifier l'intégrité
            $this->checkIntegrity();
            
            // 6. Nettoyer
            $this->cleanup();
            
            $this->logger->info("Déploiement terminé avec succès");
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du déploiement", ['error' => $e->getMessage()]);
            $this->rollback();
            return false;
        }
    }

    private function checkPrerequisites() {
        // Vérifier les versions PHP et MySQL
        $this->checkPhpVersion();
        $this->checkMysqlVersion();
        
        // Vérifier les permissions
        $this->checkPermissions();
        
        // Vérifier l'espace disque
        $this->checkDiskSpace();
    }

    private function checkPhpVersion() {
        $required = $this->config['php_version'];
        $current = PHP_VERSION;
        
        if (version_compare($current, $required, '<')) {
            throw new Exception("Version PHP requise: $required, version actuelle: $current");
        }
    }

    private function checkMysqlVersion() {
        $required = $this->config['mysql_version'];
        $current = $this->db->query('SELECT VERSION()')->fetchColumn();
        
        if (version_compare($current, $required, '<')) {
            throw new Exception("Version MySQL requise: $required, version actuelle: $current");
        }
    }

    private function checkPermissions() {
        $dirs = [
            '../cache',
            '../logs',
            '../uploads',
            '../backups'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_writable($dir)) {
                throw new Exception("Le dossier $dir n'est pas accessible en écriture");
            }
        }
    }

    private function checkDiskSpace() {
        $required = $this->config['min_disk_space'];
        $free = disk_free_space(__DIR__);
        
        if ($free < $required) {
            throw new Exception("Espace disque insuffisant. Requis: $required, Disponible: $free");
        }
    }

    private function createBackup() {
        require_once __DIR__ . '/backup.php';
        $backupManager = new BackupManager($this->db, $this->logger);
        $backupManager->runBackup();
    }

    private function updateFiles() {
        // Mettre à jour les fichiers depuis le dépôt
        $this->updateFromRepository();
        
        // Mettre à jour les dépendances
        $this->updateDependencies();
        
        // Mettre à jour les permissions
        $this->updatePermissions();
    }

    private function updateFromRepository() {
        $repo = $this->config['repository'];
        $branch = $this->config['branch'];
        
        // Cloner ou mettre à jour le dépôt
        if (!file_exists($this->deployDir . '/.git')) {
            exec("git clone $repo " . $this->deployDir);
        } else {
            exec("cd " . $this->deployDir . " && git pull origin $branch");
        }
        
        // Copier les fichiers
        $this->copyFiles();
    }

    private function copyFiles() {
        $exclude = $this->config['exclude_files'];
        $source = $this->deployDir;
        $destination = dirname(__DIR__);
        
        $this->recursiveCopy($source, $destination, $exclude);
    }

    private function recursiveCopy($source, $destination, $exclude = []) {
        $dir = opendir($source);
        
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file == '.' || $file == '..' || in_array($file, $exclude)) {
                continue;
            }
            
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;
            
            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $destPath, $exclude);
            } else {
                copy($sourcePath, $destPath);
            }
        }
        
        closedir($dir);
    }

    private function updateDependencies() {
        // Mettre à jour Composer
        exec('composer install --no-dev --optimize-autoloader');
        
        // Mettre à jour npm si nécessaire
        if (file_exists('package.json')) {
            exec('npm install --production');
        }
    }

    private function updatePermissions() {
        $dirs = [
            '../cache' => '0775',
            '../logs' => '0775',
            '../uploads' => '0775',
            '../backups' => '0775'
        ];
        
        foreach ($dirs as $dir => $permissions) {
            chmod($dir, octdec($permissions));
        }
    }

    private function updateDatabase() {
        // Appliquer les migrations
        $migrations = glob(__DIR__ . '/../sql/migrations/*.sql');
        sort($migrations);
        
        foreach ($migrations as $migration) {
            $sql = file_get_contents($migration);
            $this->db->exec($sql);
        }
    }

    private function checkIntegrity() {
        // Vérifier les fichiers essentiels
        $requiredFiles = [
            '../index.php',
            '../config/database.php',
            '../classes/Logger.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                throw new Exception("Fichier manquant: $file");
            }
        }
        
        // Vérifier la connexion à la base de données
        $this->db->query('SELECT 1');
    }

    private function cleanup() {
        // Supprimer les fichiers temporaires
        $tempFiles = glob($this->deployDir . '/*');
        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Vider le cache
        $this->clearCache();
    }

    private function clearCache() {
        $cacheDirs = [
            '../cache',
            '../tmp'
        ];
        
        foreach ($cacheDirs as $dir) {
            if (file_exists($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }

    private function rollback() {
        $this->logger->info("Début du rollback");
        
        // Restaurer le backup
        $this->restoreBackup();
        
        // Nettoyer
        $this->cleanup();
        
        $this->logger->info("Rollback terminé");
    }

    private function restoreBackup() {
        // Implémenter la restauration du backup
        // Cette méthode devrait être implémentée en fonction
        // de la stratégie de backup choisie
    }
}

// Exécution du déploiement
try {
    $logger = new Logger($db);
    $deployManager = new DeployManager($db, $logger);
    $deployManager->deploy();
} catch (Exception $e) {
    error_log("Erreur lors du déploiement: " . $e->getMessage());
} 