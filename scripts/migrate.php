<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/deploy.php';

class Migration {
    private $pdo;
    private $migrationsTable;
    private $migrationsPath;
    
    public function __construct() {
        global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
        $this->pdo = new PDO(
            "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $this->migrationsTable = DEPLOY_CONFIG['migrations']['table'];
        $this->migrationsPath = DEPLOY_CONFIG['migrations']['path'];
        
        $this->createMigrationsTable();
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }
    
    public function getRanMigrations() {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->migrationsTable} ORDER BY batch, migration");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getNextBatchNumber() {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM {$this->migrationsTable}");
        return (int)$stmt->fetchColumn() + 1;
    }
    
    public function run() {
        $files = glob($this->migrationsPath . '/*.sql');
        $ranMigrations = $this->getRanMigrations();
        $nextBatch = $this->getNextBatchNumber();
        
        foreach ($files as $file) {
            $migration = basename($file);
            
            if (!in_array($migration, $ranMigrations)) {
                echo "Exécution de la migration: $migration\n";
                
                try {
                    $this->pdo->beginTransaction();
                    
                    // Exécuter le fichier SQL
                    $sql = file_get_contents($file);
                    $this->pdo->exec($sql);
                    
                    // Enregistrer la migration
                    $stmt = $this->pdo->prepare(
                        "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
                    );
                    $stmt->execute([$migration, $nextBatch]);
                    
                    $this->pdo->commit();
                    echo "Migration réussie: $migration\n";
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    echo "Erreur lors de la migration $migration: " . $e->getMessage() . "\n";
                    exit(1);
                }
            }
        }
        
        echo "Toutes les migrations ont été exécutées avec succès.\n";
    }
    
    public function rollback($steps = 1) {
        $batch = $this->getNextBatchNumber() - 1;
        $targetBatch = max(0, $batch - $steps);
        
        $stmt = $this->pdo->prepare(
            "SELECT migration FROM {$this->migrationsTable} WHERE batch > ? ORDER BY batch DESC, migration DESC"
        );
        $stmt->execute([$targetBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($migrations as $migration) {
            echo "Annulation de la migration: $migration\n";
            
            try {
                $this->pdo->beginTransaction();
                
                // Exécuter le fichier de rollback
                $rollbackFile = $this->migrationsPath . '/rollback/' . $migration;
                if (file_exists($rollbackFile)) {
                    $sql = file_get_contents($rollbackFile);
                    $this->pdo->exec($sql);
                }
                
                // Supprimer l'enregistrement de migration
                $stmt = $this->pdo->prepare(
                    "DELETE FROM {$this->migrationsTable} WHERE migration = ?"
                );
                $stmt->execute([$migration]);
                
                $this->pdo->commit();
                echo "Migration annulée avec succès: $migration\n";
            } catch (Exception $e) {
                $this->pdo->rollBack();
                echo "Erreur lors de l'annulation de la migration $migration: " . $e->getMessage() . "\n";
                exit(1);
            }
        }
        
        echo "Rollback terminé avec succès.\n";
    }
}

// Exécution du script
$migration = new Migration();

if (isset($argv[1]) && $argv[1] === 'rollback') {
    $steps = isset($argv[2]) ? (int)$argv[2] : 1;
    $migration->rollback($steps);
} else {
    $migration->run();
} 