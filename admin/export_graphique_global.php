<?php
// Désactiver la mise en mémoire tampon de sortie
ob_start();

// Inclure les fichiers nécessaires sans générer de sortie
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer les paramètres
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$antenne_selectionnee = $_GET['antenne'] ?? 'Toutes';

// Récupérer les données
$where_antenne = $antenne_selectionnee !== 'Toutes' ? "AND antenne = :antenne" : "";
$params = [$annee];
if ($antenne_selectionnee !== 'Toutes') {
    $params[] = $antenne_selectionnee;
}

$stmt = $pdo->prepare("
    SELECT 
        MONTH(date_rapport) as mois,
        SUM(hommes_3_12) as hommes_0_12,
        SUM(hommes_13_19) as hommes_13_19,
        SUM(hommes_20_49) as hommes_20_49,
        SUM(hommes_50_plus) as hommes_50_plus,
        SUM(femmes_3_12) as femmes_0_12,
        SUM(femmes_13_19) as femmes_13_19,
        SUM(femmes_20_49) as femmes_20_49,
        SUM(femmes_50_plus) as femmes_50_plus
    FROM rapports_hemodialyse 
    WHERE YEAR(date_rapport) = ? 
        AND statut = 'validé'
        $where_antenne
    GROUP BY MONTH(date_rapport)
    ORDER BY mois
");
$stmt->execute($params);
$donnees_mensuelles = $stmt->fetchAll();

// Tableau des noms de mois en français
$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

// Calculer les moyennes annuelles
$moyennes = [
    'hommes_0_12' => 0, 'hommes_13_19' => 0, 'hommes_20_49' => 0, 'hommes_50_plus' => 0,
    'femmes_0_12' => 0, 'femmes_13_19' => 0, 'femmes_20_49' => 0, 'femmes_50_plus' => 0
];
$nb_mois = count($donnees_mensuelles);

foreach ($donnees_mensuelles as $donnees) {
    foreach ($moyennes as $key => $value) {
        $moyennes[$key] += $donnees[$key];
    }
}

if ($nb_mois > 0) {
    foreach ($moyennes as $key => $value) {
        $moyennes[$key] = round($value / $nb_mois);
    }
}

// Nettoyer la mémoire tampon
ob_end_clean();

// Export en XLSX
require '../vendor/autoload.php';

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Titre
$sheet->setCellValue('A1', 'Statistiques des Dialyses par Sexe et Âge - ' . $annee);
$sheet->mergeCells('A1:K1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// En-têtes
$sheet->setCellValue('A3', 'MOIS');
$sheet->mergeCells('B3:F3');
$sheet->setCellValue('B3', 'Hommes');
$sheet->mergeCells('G3:K3');
$sheet->setCellValue('G3', 'Femmes');

$sheet->setCellValue('A4', 'MOIS');
$sheet->setCellValue('B4', '0-12 ans');
$sheet->setCellValue('C4', '13-19 ans');
$sheet->setCellValue('D4', '20-49 ans');
$sheet->setCellValue('E4', '≥50 ans');
$sheet->setCellValue('F4', 'TOTAL');
$sheet->setCellValue('G4', '0-12 ans');
$sheet->setCellValue('H4', '13-19 ans');
$sheet->setCellValue('I4', '20-49 ans');
$sheet->setCellValue('J4', '≥50 ans');
$sheet->setCellValue('K4', 'TOTAL');

// Données
$row = 5;
foreach ($donnees_mensuelles as $donnees) {
    $sheet->setCellValue('A' . $row, $mois_fr[$donnees['mois']]);
    $sheet->setCellValue('B' . $row, $donnees['hommes_0_12']);
    $sheet->setCellValue('C' . $row, $donnees['hommes_13_19']);
    $sheet->setCellValue('D' . $row, $donnees['hommes_20_49']);
    $sheet->setCellValue('E' . $row, $donnees['hommes_50_plus']);
    $sheet->setCellValue('F' . $row, $donnees['hommes_0_12'] + $donnees['hommes_13_19'] + $donnees['hommes_20_49'] + $donnees['hommes_50_plus']);
    $sheet->setCellValue('G' . $row, $donnees['femmes_0_12']);
    $sheet->setCellValue('H' . $row, $donnees['femmes_13_19']);
    $sheet->setCellValue('I' . $row, $donnees['femmes_20_49']);
    $sheet->setCellValue('J' . $row, $donnees['femmes_50_plus']);
    $sheet->setCellValue('K' . $row, $donnees['femmes_0_12'] + $donnees['femmes_13_19'] + $donnees['femmes_20_49'] + $donnees['femmes_50_plus']);
    $row++;
}

// Moyennes
$sheet->setCellValue('A' . $row, 'Effectif moyen');
$sheet->setCellValue('B' . $row, $moyennes['hommes_0_12']);
$sheet->setCellValue('C' . $row, $moyennes['hommes_13_19']);
$sheet->setCellValue('D' . $row, $moyennes['hommes_20_49']);
$sheet->setCellValue('E' . $row, $moyennes['hommes_50_plus']);
$sheet->setCellValue('F' . $row, $moyennes['hommes_0_12'] + $moyennes['hommes_13_19'] + $moyennes['hommes_20_49'] + $moyennes['hommes_50_plus']);
$sheet->setCellValue('G' . $row, $moyennes['femmes_0_12']);
$sheet->setCellValue('H' . $row, $moyennes['femmes_13_19']);
$sheet->setCellValue('I' . $row, $moyennes['femmes_20_49']);
$sheet->setCellValue('J' . $row, $moyennes['femmes_50_plus']);
$sheet->setCellValue('K' . $row, $moyennes['femmes_0_12'] + $moyennes['femmes_13_19'] + $moyennes['femmes_20_49'] + $moyennes['femmes_50_plus']);

// Style
$sheet->getStyle('A3:K4')->getFont()->setBold(true);
$sheet->getStyle('A3:K' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3:K' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Envoyer le fichier
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="statistiques_hemodialyse_' . $annee . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit; 