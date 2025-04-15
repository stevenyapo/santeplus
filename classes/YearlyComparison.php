<?php
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/../includes/Logger.php';

class YearlyComparison {
    private $db;
    private $cache;
    private $logger;

    public function __construct($db) {
        $this->db = $db;
        $this->cache = Cache::getInstance();
        $this->logger = Logger::getInstance($db);
    }

    public function getConsultationsComparison($startYear, $endYear) {
        $cacheKey = "consultations_comparison_{$startYear}_{$endYear}";
        
        // Vérifier le cache
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        try {
            // Récupérer les données des consultations
            $consultationsData = $this->getConsultationsData($startYear, $endYear);
            
            // Récupérer les données des diagnostics
            $diagnosticsData = $this->getDiagnosticsData($startYear, $endYear);
            
            // Récupérer les données des prescriptions
            $prescriptionsData = $this->getPrescriptionsData($startYear, $endYear);
            
            // Récupérer les statistiques générales
            $statsData = $this->getStatsData($startYear, $endYear);

            // Organiser les données pour la réponse
            $data = [
                'consultations' => [
                    'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
                    'datasets' => $consultationsData
                ],
                'diagnostics' => [
                    'labels' => array_keys($diagnosticsData['labels']),
                    'datasets' => $diagnosticsData['datasets']
                ],
                'prescriptions' => [
                    'labels' => array_keys($prescriptionsData['labels']),
                    'datasets' => $prescriptionsData['datasets']
                ],
                'stats' => $statsData
            ];

            // Mettre en cache les données
            $this->cache->set($cacheKey, $data, 3600); // Cache pour 1 heure

            return $data;

        } catch (PDOException $e) {
            $this->logger->log("Erreur lors de la récupération des données de comparaison : " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    private function getConsultationsData($startYear, $endYear) {
        $stmt = $this->db->prepare("
            SELECT 
                YEAR(date_consultation) as year,
                MONTH(date_consultation) as month,
                COUNT(*) as total_consultations,
                COUNT(DISTINCT patient_id) as unique_patients,
                AVG(duree) as average_duration
            FROM consultations
            WHERE YEAR(date_consultation) BETWEEN ? AND ?
            GROUP BY YEAR(date_consultation), MONTH(date_consultation)
            ORDER BY year, month
        ");

        $stmt->execute([$startYear, $endYear]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les données par année
        $result = [];
        foreach ($data as $row) {
            $year = $row['year'];
            if (!isset($result[$year])) {
                $result[$year] = array_fill(1, 12, 0);
            }
            $result[$year][$row['month']] = $row['total_consultations'];
        }

        // Convertir en format pour Chart.js
        $datasets = [];
        foreach ($result as $year => $months) {
            $datasets[] = [
                'label' => $year,
                'data' => array_values($months),
                'borderColor' => $this->getRandomColor(),
                'tension' => 0.1
            ];
        }

        return $datasets;
    }

    private function getDiagnosticsData($startYear, $endYear) {
        $stmt = $this->db->prepare("
            SELECT 
                YEAR(c.date_consultation) as year,
                d.nom as diagnostic,
                COUNT(*) as count
            FROM consultations c
            JOIN diagnostics d ON c.id = d.consultation_id
            WHERE YEAR(c.date_consultation) BETWEEN ? AND ?
            GROUP BY YEAR(c.date_consultation), d.nom
            ORDER BY year, count DESC
        ");

        $stmt->execute([$startYear, $endYear]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les données
        $labels = [];
        $datasets = [];
        $years = [];

        foreach ($data as $row) {
            $year = $row['year'];
            $diagnostic = $row['diagnostic'];
            
            if (!in_array($year, $years)) {
                $years[] = $year;
            }
            
            if (!isset($labels[$diagnostic])) {
                $labels[$diagnostic] = [];
            }
            
            $labels[$diagnostic][$year] = $row['count'];
        }

        // Créer les datasets
        foreach ($years as $year) {
            $dataset = [
                'label' => $year,
                'data' => [],
                'backgroundColor' => $this->getRandomColor()
            ];
            
            foreach ($labels as $diagnostic => $counts) {
                $dataset['data'][] = $counts[$year] ?? 0;
            }
            
            $datasets[] = $dataset;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    private function getPrescriptionsData($startYear, $endYear) {
        $stmt = $this->db->prepare("
            SELECT 
                YEAR(c.date_consultation) as year,
                m.nom as medicament,
                COUNT(*) as count
            FROM consultations c
            JOIN prescriptions p ON c.id = p.consultation_id
            JOIN medicaments m ON p.medicament_id = m.id
            WHERE YEAR(c.date_consultation) BETWEEN ? AND ?
            GROUP BY YEAR(c.date_consultation), m.nom
            ORDER BY year, count DESC
        ");

        $stmt->execute([$startYear, $endYear]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les données
        $labels = [];
        $datasets = [];
        $years = [];

        foreach ($data as $row) {
            $year = $row['year'];
            $medicament = $row['medicament'];
            
            if (!in_array($year, $years)) {
                $years[] = $year;
            }
            
            if (!isset($labels[$medicament])) {
                $labels[$medicament] = [];
            }
            
            $labels[$medicament][$year] = $row['count'];
        }

        // Créer les datasets
        foreach ($years as $year) {
            $dataset = [
                'label' => $year,
                'data' => [],
                'backgroundColor' => $this->getRandomColor()
            ];
            
            foreach ($labels as $medicament => $counts) {
                $dataset['data'][] = $counts[$year] ?? 0;
            }
            
            $datasets[] = $dataset;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    private function getStatsData($startYear, $endYear) {
        $stmt = $this->db->prepare("
            SELECT 
                YEAR(date_consultation) as year,
                COUNT(*) as total_consultations,
                COUNT(DISTINCT patient_id) as unique_patients,
                AVG(duree) as average_duration
            FROM consultations
            WHERE YEAR(date_consultation) BETWEEN ? AND ?
            GROUP BY YEAR(date_consultation)
            ORDER BY year
        ");

        $stmt->execute([$startYear, $endYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRandomColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
} 