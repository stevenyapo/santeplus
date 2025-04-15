<?php
require_once __DIR__ . '/../config/logger.php';

class LogCleaner {
    private $config;
    private $logger;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/logger.php';
        $this->logger = Logger::getInstance();
    }
    
    public function clean() {
        foreach ($this->config['log_types'] as $type => $settings) {
            if (!$settings['enabled']) {
                continue;
            }
            
            $logPath = $this->config['log_path'] . $settings['path'];
            $this->cleanDirectory($logPath);
        }
    }
    
    private function cleanDirectory($directory) {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = glob($directory . '*.log');
        if (count($files) <= $this->config['max_files']) {
            return;
        }
        
        // Trier les fichiers par date de modification
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Supprimer les fichiers les plus anciens
        $filesToDelete = array_slice($files, 0, count($files) - $this->config['max_files']);
        foreach ($filesToDelete as $file) {
            if (unlink($file)) {
                $this->logger->info("Fichier de log supprimé: $file");
            } else {
                $this->logger->error("Impossible de supprimer le fichier de log: $file");
            }
        }
    }
}

// Exécution du script
$cleaner = new LogCleaner();
$cleaner->clean(); 