<?php
// Démarrer la mise en tampon de sortie
ob_start();

require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de rapport invalide');
}

// Récupérer les détails du rapport
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

    // Calculer les totaux par sexe
    $total_femmes = $rapport['femmes_3_12'] + $rapport['femmes_13_19'] + 
                    $rapport['femmes_20_49'] + $rapport['femmes_50_plus'];
    $total_hommes = $rapport['hommes_3_12'] + $rapport['hommes_13_19'] + 
                    $rapport['hommes_20_49'] + $rapport['hommes_50_plus'];

    // Créer le contenu HTML du PDF
    $html = '
    <h1 style="text-align: center;">Rapport d\'Hémodialyse</h1>
    <h2 style="text-align: center;">' . htmlspecialchars($rapport['antenne']) . '</h2>
    <p style="text-align: center;">Date du rapport : ' . date('d/m/Y', strtotime($rapport['date_rapport'])) . '</p>
    <p style="text-align: center;">Médecin : ' . htmlspecialchars($rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom']) . '</p>
    
    <h3>Statistiques générales</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Total patients dialysés</th>
            <td>' . $rapport['total_patients_dialyses'] . '</td>
        </tr>
        <tr>
            <th>Générateurs fonctionnels</th>
            <td>' . $rapport['nb_generateurs_fonctionnels'] . '</td>
        </tr>
        <tr>
            <th>Postes de dialyse</th>
            <td>' . $rapport['nb_postes_dialyse'] . '</td>
        </tr>
        <tr>
            <th>Séances d\'urgence</th>
            <td>' . $rapport['nb_seances_urgence'] . '</td>
        </tr>
        <tr>
            <th>Patients en séjour</th>
            <td>' . $rapport['nb_patients_sejour'] . '</td>
        </tr>
        <tr>
            <th>Décès</th>
            <td>' . $rapport['nb_deces'] . '</td>
        </tr>
    </table>

    <h3>Répartition des patients par sexe et âge</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Tranche d\'âge</th>
            <th>Femmes</th>
            <th>Hommes</th>
            <th>Total</th>
        </tr>
        <tr>
            <td>3-12 ans</td>
            <td>' . $rapport['femmes_3_12'] . '</td>
            <td>' . $rapport['hommes_3_12'] . '</td>
            <td>' . ($rapport['femmes_3_12'] + $rapport['hommes_3_12']) . '</td>
        </tr>
        <tr>
            <td>13-19 ans</td>
            <td>' . $rapport['femmes_13_19'] . '</td>
            <td>' . $rapport['hommes_13_19'] . '</td>
            <td>' . ($rapport['femmes_13_19'] + $rapport['hommes_13_19']) . '</td>
        </tr>
        <tr>
            <td>20-49 ans</td>
            <td>' . $rapport['femmes_20_49'] . '</td>
            <td>' . $rapport['hommes_20_49'] . '</td>
            <td>' . ($rapport['femmes_20_49'] + $rapport['hommes_20_49']) . '</td>
        </tr>
        <tr>
            <td>50 ans et plus</td>
            <td>' . $rapport['femmes_50_plus'] . '</td>
            <td>' . $rapport['hommes_50_plus'] . '</td>
            <td>' . ($rapport['femmes_50_plus'] + $rapport['hommes_50_plus']) . '</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>' . $total_femmes . '</strong></td>
            <td><strong>' . $total_hommes . '</strong></td>
            <td><strong>' . $rapport['total_patients_dialyses'] . '</strong></td>
        </tr>
    </table>';

    if ($rapport['commentaire_admin']) {
        $html .= '
        <h3>Commentaire de l\'administrateur</h3>
        <p>' . nl2br(htmlspecialchars($rapport['commentaire_admin'])) . '</p>';
    }

    // Nettoyer le tampon de sortie
    ob_end_clean();

    // Générer le PDF
    require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

    // Créer une nouvelle instance de TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Définir les informations du document
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SantéPlus');
    $pdf->SetTitle('Rapport d\'Hémodialyse - ' . $rapport['antenne']);

    // Définir les marges
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Définir les sauts de page automatiques
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Ajouter une page
    $pdf->AddPage();

    // Écrire le contenu HTML
    $pdf->writeHTML($html, true, false, true, false, '');

    // Générer le PDF
    $pdf->Output('rapport_hemodialyse_' . $rapport['id_rapport'] . '.pdf', 'D');

} catch (Exception $e) {
    // Nettoyer le tampon de sortie en cas d'erreur
    ob_end_clean();
    die('Erreur lors de la génération du PDF : ' . $e->getMessage());
} 