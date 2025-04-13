<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Accès non autorisé');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de rapport invalide');
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
        FROM rapports_hemodialyse r
        JOIN medecins m ON r.id_medecin = m.id_medecin
        WHERE r.id_rapport = ?
    ");
    $stmt->execute([$_GET['id']]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rapport) {
        die('Rapport non trouvé');
    }

    // Créer une nouvelle instance de TCPDF
    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetFont('helvetica', 'B', 15);
            $this->Cell(0, 15, 'Rapport d\'hémodialyse', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Définir les informations du document
    $pdf->SetCreator('SantePlus');
    $pdf->SetAuthor('SantePlus');
    $pdf->SetTitle('Rapport Hémodialyse - ' . date('d/m/Y', strtotime($rapport['date_rapport'])));

    // Définir les marges
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Définir l'auto-page-break
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Ajouter une page
    $pdf->AddPage();

    // Définir la police
    $pdf->SetFont('helvetica', '', 11);

    // Informations générales
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Informations générales', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $info_generale = array(
        'Date du rapport' => date('d/m/Y', strtotime($rapport['date_rapport'])),
        'Antenne' => $rapport['antenne'],
        'Médecin' => $rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom'],
        'Statut' => ucfirst($rapport['statut'])
    );

    foreach($info_generale as $label => $value) {
        $pdf->Cell(60, 7, $label . ' : ', 0, 0);
        $pdf->Cell(0, 7, $value, 0, 1);
    }

    $pdf->Ln(5);

    // Statistiques générales
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Statistiques générales', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);

    $stats_generale = array(
        'Total patients dialysés' => $rapport['total_patients_dialyses'],
        'Générateurs fonctionnels' => $rapport['nb_generateurs_fonctionnels'],
        'Postes de dialyse' => $rapport['nb_postes_dialyse'],
        'Séances d\'urgence' => $rapport['nb_seances_urgence'],
        'Patients en séjour' => $rapport['nb_patients_sejour'],
        'Décès' => $rapport['nb_deces']
    );

    foreach($stats_generale as $label => $value) {
        $pdf->Cell(60, 7, $label . ' : ', 0, 0);
        $pdf->Cell(0, 7, $value, 0, 1);
    }

    $pdf->Ln(5);

    // Répartition des patients
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Répartition des patients par sexe et âge', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);

    // En-têtes du tableau
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(50, 7, 'Tranche d\'âge', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Femmes', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Hommes', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Total', 1, 1, 'C', true);

    // Données du tableau
    $tranches_age = array(
        '3-12 ans' => array($rapport['femmes_3_12'], $rapport['hommes_3_12']),
        '13-19 ans' => array($rapport['femmes_13_19'], $rapport['hommes_13_19']),
        '20-49 ans' => array($rapport['femmes_20_49'], $rapport['hommes_20_49']),
        '50 ans et plus' => array($rapport['femmes_50_plus'], $rapport['hommes_50_plus'])
    );

    foreach($tranches_age as $tranche => $data) {
        $pdf->Cell(50, 7, $tranche, 1, 0, 'L');
        $pdf->Cell(35, 7, $data[0], 1, 0, 'C');
        $pdf->Cell(35, 7, $data[1], 1, 0, 'C');
        $pdf->Cell(35, 7, $data[0] + $data[1], 1, 1, 'C');
    }

    // Total
    $pdf->SetFillColor(220, 220, 220);
    $total_femmes = array_sum(array_column($tranches_age, 0));
    $total_hommes = array_sum(array_column($tranches_age, 1));
    $pdf->Cell(50, 7, 'Total', 1, 0, 'L', true);
    $pdf->Cell(35, 7, $total_femmes, 1, 0, 'C', true);
    $pdf->Cell(35, 7, $total_hommes, 1, 0, 'C', true);
    $pdf->Cell(35, 7, $total_femmes + $total_hommes, 1, 1, 'C', true);

    // Commentaire de l'administrateur si présent
    if ($rapport['commentaire_admin']) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Commentaire de l\'administrateur', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 7, $rapport['commentaire_admin'], 0, 'L');
    }

    // Générer le PDF
    $filename = 'rapport_hemodialyse_' . date('Y-m-d', strtotime($rapport['date_rapport'])) . '_' . 
                strtolower($rapport['antenne']) . '.pdf';
    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    die('Erreur lors de la génération du PDF : ' . $e->getMessage());
} 