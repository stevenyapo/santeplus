<?php
// Désactiver la mise en mémoire tampon de sortie
ob_start();

// Inclure les fichiers nécessaires
require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/init.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer le format d'exportation
$format = $_GET['export'] ?? 'excel';
$periode = $_GET['periode'] ?? 'mois';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01', strtotime('-11 months'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-t');

try {
    // Récupérer les données
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_rapport, '%Y-%m') as mois,
            COUNT(*) as total_rapports,
            SUM(CASE WHEN statut = 'validé' THEN 1 ELSE 0 END) as rapports_valides,
            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as rapports_en_attente,
            SUM(CASE WHEN statut = 'refuse' THEN 1 ELSE 0 END) as rapports_refuses
        FROM rapports_hemodialyse 
        WHERE date_rapport BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_rapport, '%Y-%m')
        ORDER BY mois
    ");
    $stmt->execute([$date_debut, $date_fin]);
    $donnees = $stmt->fetchAll();

    // Préparer les données pour l'exportation
    $export_data = [];
    foreach ($donnees as $donnee) {
        $export_data[] = [
            'Mois' => date('F Y', strtotime($donnee['mois'] . '-01')),
            'Total Rapports' => $donnee['total_rapports'],
            'Rapports Validés' => $donnee['rapports_valides'],
            'Rapports en Attente' => $donnee['rapports_en_attente'],
            'Rapports Refusés' => $donnee['rapports_refuses']
        ];
    }

    // Exporter selon le format demandé
    switch ($format) {
        case 'excel':
            // Vérifier si PhpSpreadsheet est installé
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new Exception('La bibliothèque PhpSpreadsheet n\'est pas installée. Veuillez l\'installer avec Composer.');
            }

            // Définir les en-têtes pour Excel
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="tableau_de_bord_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            // Créer le fichier Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Ajouter les en-têtes
            $headers = array_keys($export_data[0]);
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }
            
            // Ajouter les données
            $row = 2;
            foreach ($export_data as $data) {
                $col = 1;
                foreach ($data as $value) {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Créer le fichier
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            break;
            
        case 'pdf':
            // Créer le PDF avec mPDF
            $mpdf = new \Mpdf\Mpdf([
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);
            
            // Ajouter le contenu
            $html = '<h1 style="text-align: center;">Tableau de bord - ' . date('d/m/Y') . '</h1>';
            $html .= '<table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse;">';
            
            // En-têtes
            $html .= '<tr style="background-color: #f5f5f5;">';
            foreach (array_keys($export_data[0]) as $header) {
                $html .= '<th style="text-align: left;">' . $header . '</th>';
            }
            $html .= '</tr>';
            
            // Données
            foreach ($export_data as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . $value . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            
            // Ajouter le contenu au PDF
            $mpdf->WriteHTML($html);
            
            // Générer le PDF
            $mpdf->Output('tableau_de_bord_' . date('Y-m-d') . '.pdf', 'D');
            break;
            
        case 'csv':
            // Définir les en-têtes pour CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="tableau_de_bord_' . date('Y-m-d') . '.csv"');
            
            // Créer le fichier CSV
            $output = fopen('php://output', 'w');
            
            // Ajouter les en-têtes
            fputcsv($output, array_keys($export_data[0]));
            
            // Ajouter les données
            foreach ($export_data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            break;
            
        default:
            throw new Exception('Format d\'exportation non supporté');
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Erreur lors de l\'exportation : ' . $e->getMessage();
    exit();
} 