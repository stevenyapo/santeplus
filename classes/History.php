<?php
class History {
    private $db;
    private $logger;

    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function logChange($table, $recordId, $action, $oldData = null, $newData = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO history_logs 
                (table_name, record_id, action, old_data, new_data, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt->execute([
                $table,
                $recordId,
                $action,
                $oldData ? json_encode($oldData) : null,
                $newData ? json_encode($newData) : null,
                $userId
            ]);

            // Log l'action
            $this->logger->info("Modification historique", [
                'table' => $table,
                'record_id' => $recordId,
                'action' => $action,
                'user_id' => $userId
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'enregistrement de l'historique", [
                'error' => $e->getMessage(),
                'table' => $table,
                'record_id' => $recordId
            ]);
            return false;
        }
    }

    public function getHistory($table, $recordId = null, $limit = 50) {
        try {
            $sql = "SELECT * FROM history_logs WHERE table_name = ?";
            $params = [$table];

            if ($recordId !== null) {
                $sql .= " AND record_id = ?";
                $params[] = $recordId;
            }

            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération de l'historique", [
                'error' => $e->getMessage(),
                'table' => $table,
                'record_id' => $recordId
            ]);
            return [];
        }
    }

    public function getHistoryHtml($table, $recordId = null) {
        $history = $this->getHistory($table, $recordId);
        
        if (empty($history)) {
            return '<div class="alert alert-info">Aucun historique disponible</div>';
        }

        $html = '<div class="history-timeline">';
        
        foreach ($history as $entry) {
            $html .= $this->formatHistoryEntry($entry);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function formatHistoryEntry($entry) {
        $date = new DateTime($entry['created_at']);
        $formattedDate = $date->format('d/m/Y H:i:s');
        
        $actionClass = $this->getActionClass($entry['action']);
        $actionIcon = $this->getActionIcon($entry['action']);
        
        $html = '<div class="history-entry ' . $actionClass . '">';
        $html .= '<div class="history-icon">' . $actionIcon . '</div>';
        $html .= '<div class="history-content">';
        $html .= '<div class="history-header">';
        $html .= '<span class="history-action">' . $this->getActionText($entry['action']) . '</span>';
        $html .= '<span class="history-date">' . $formattedDate . '</span>';
        $html .= '</div>';
        
        if ($entry['old_data'] || $entry['new_data']) {
            $html .= '<div class="history-details">';
            
            if ($entry['old_data']) {
                $html .= '<div class="history-old">';
                $html .= '<strong>Anciennes données:</strong>';
                $html .= '<pre>' . htmlspecialchars($entry['old_data']) . '</pre>';
                $html .= '</div>';
            }
            
            if ($entry['new_data']) {
                $html .= '<div class="history-new">';
                $html .= '<strong>Nouvelles données:</strong>';
                $html .= '<pre>' . htmlspecialchars($entry['new_data']) . '</pre>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    private function getActionClass($action) {
        $classes = [
            'create' => 'history-create',
            'update' => 'history-update',
            'delete' => 'history-delete'
        ];
        return $classes[$action] ?? 'history-default';
    }

    private function getActionIcon($action) {
        $icons = [
            'create' => '<i class="fas fa-plus-circle"></i>',
            'update' => '<i class="fas fa-edit"></i>',
            'delete' => '<i class="fas fa-trash-alt"></i>'
        ];
        return $icons[$action] ?? '<i class="fas fa-info-circle"></i>';
    }

    private function getActionText($action) {
        $texts = [
            'create' => 'Création',
            'update' => 'Modification',
            'delete' => 'Suppression'
        ];
        return $texts[$action] ?? $action;
    }
} 