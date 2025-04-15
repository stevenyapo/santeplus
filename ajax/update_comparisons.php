<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
checkLogin();

// Récupérer les paramètres
$startYear = isset($_GET['startYear']) ? (int)$_GET['startYear'] : date('Y') - 1;
$endYear = isset($_GET['endYear']) ? (int)$_GET['endYear'] : date('Y');

// Valider les années
if ($startYear > $endYear) {
    $temp = $startYear;
    $startYear = $endYear;
    $endYear = $temp;
}

// Préparer la réponse
$response = [
    'consultations' => [
        'labels' => [],
        'datasets' => []
    ],
    'diagnostics' => [
        'labels' => [],
        'datasets' => []
    ],
    'prescriptions' => [
        'labels' => [],
        'datasets' => []
    ],
    'stats' => []
];

// Récupérer les données des consultations
$consultationsQuery = "SELECT 
    YEAR(date_consultation) as year,
    MONTH(date_consultation) as month,
    COUNT(*) as count
FROM consultations
WHERE YEAR(date_consultation) BETWEEN ? AND ?
GROUP BY YEAR(date_consultation), MONTH(date_consultation)
ORDER BY year, month";

$stmt = $pdo->prepare($consultationsQuery);
$stmt->execute([$startYear, $endYear]);
$consultationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les données des consultations
$consultationsByYear = [];
foreach ($consultationsData as $row) {
    if (!isset($consultationsByYear[$row['year']])) {
        $consultationsByYear[$row['year']] = array_fill(1, 12, 0);
    }
    $consultationsByYear[$row['year']][$row['month']] = $row['count'];
}

// Préparer les données pour le graphique des consultations
$response['consultations']['labels'] = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
foreach ($consultationsByYear as $year => $data) {
    $response['consultations']['datasets'][] = [
        'label' => $year,
        'data' => array_values($data),
        'borderColor' => getRandomColor(),
        'tension' => 0.1
    ];
}

// Récupérer les données des diagnostics
$diagnosticsQuery = "SELECT 
    YEAR(c.date_consultation) as year,
    d.nom as diagnostic,
    COUNT(*) as count
FROM consultations c
JOIN diagnostics d ON c.id = d.consultation_id
WHERE YEAR(c.date_consultation) BETWEEN ? AND ?
GROUP BY YEAR(c.date_consultation), d.nom
ORDER BY year, count DESC";

$stmt = $pdo->prepare($diagnosticsQuery);
$stmt->execute([$startYear, $endYear]);
$diagnosticsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les données des diagnostics
$diagnosticsByYear = [];
foreach ($diagnosticsData as $row) {
    if (!isset($diagnosticsByYear[$row['year']])) {
        $diagnosticsByYear[$row['year']] = [];
    }
    $diagnosticsByYear[$row['year']][$row['diagnostic']] = $row['count'];
}

// Préparer les données pour le graphique des diagnostics
$allDiagnostics = array_unique(array_column($diagnosticsData, 'diagnostic'));
$response['diagnostics']['labels'] = array_values($allDiagnostics);
foreach ($diagnosticsByYear as $year => $data) {
    $dataset = [
        'label' => $year,
        'data' => [],
        'backgroundColor' => getRandomColor()
    ];
    foreach ($allDiagnostics as $diagnostic) {
        $dataset['data'][] = $data[$diagnostic] ?? 0;
    }
    $response['diagnostics']['datasets'][] = $dataset;
}

// Récupérer les données des prescriptions
$prescriptionsQuery = "SELECT 
    YEAR(c.date_consultation) as year,
    m.nom as medicament,
    COUNT(*) as count
FROM consultations c
JOIN prescriptions p ON c.id = p.consultation_id
JOIN medicaments m ON p.medicament_id = m.id
WHERE YEAR(c.date_consultation) BETWEEN ? AND ?
GROUP BY YEAR(c.date_consultation), m.nom
ORDER BY year, count DESC";

$stmt = $pdo->prepare($prescriptionsQuery);
$stmt->execute([$startYear, $endYear]);
$prescriptionsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les données des prescriptions
$prescriptionsByYear = [];
foreach ($prescriptionsData as $row) {
    if (!isset($prescriptionsByYear[$row['year']])) {
        $prescriptionsByYear[$row['year']] = [];
    }
    $prescriptionsByYear[$row['year']][$row['medicament']] = $row['count'];
}

// Préparer les données pour le graphique des prescriptions
$allMedicaments = array_unique(array_column($prescriptionsData, 'medicament'));
$response['prescriptions']['labels'] = array_values($allMedicaments);
foreach ($prescriptionsByYear as $year => $data) {
    $dataset = [
        'label' => $year,
        'data' => [],
        'backgroundColor' => getRandomColor()
    ];
    foreach ($allMedicaments as $medicament) {
        $dataset['data'][] = $data[$medicament] ?? 0;
    }
    $response['prescriptions']['datasets'][] = $dataset;
}

// Récupérer les statistiques générales
$statsQuery = "SELECT 
    YEAR(date_consultation) as year,
    COUNT(*) as total_consultations,
    COUNT(DISTINCT patient_id) as unique_patients,
    AVG(duree) as average_duration
FROM consultations
WHERE YEAR(date_consultation) BETWEEN ? AND ?
GROUP BY YEAR(date_consultation)
ORDER BY year";

$stmt = $pdo->prepare($statsQuery);
$stmt->execute([$startYear, $endYear]);
$response['stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour générer des couleurs aléatoires
function getRandomColor() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

// Envoyer la réponse en JSON
header('Content-Type: application/json');
echo json_encode($response); 