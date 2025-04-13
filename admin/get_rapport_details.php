<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure uniquement la configuration de la base de données
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de rapport invalide']);
    exit();
}

try {
    // Vérifier si la table existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'rapports_hemodialyse'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("La table rapports_hemodialyse n'existe pas");
    }

    // Vérifier si la table medecins existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'medecins'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("La table medecins n'existe pas");
    }

    $stmt = $pdo->prepare("
        SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
        FROM rapports_hemodialyse r
        JOIN medecins m ON r.id_medecin = m.id_medecin
        WHERE r.id_rapport = ?
    ");
    $stmt->execute([$_GET['id']]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rapport) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Rapport non trouvé']);
        exit();
    }

    // Construire le HTML pour les détails du rapport
    $html = '
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Informations générales</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Date du rapport</th>
                        <td>' . date('d/m/Y', strtotime($rapport['date_rapport'])) . '</td>
                    </tr>
                    <tr>
                        <th>Antenne</th>
                        <td>' . htmlspecialchars($rapport['antenne']) . '</td>
                    </tr>
                    <tr>
                        <th>Médecin</th>
                        <td>' . htmlspecialchars($rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom']) . '</td>
                    </tr>
                    <tr>
                        <th>Statut</th>
                        <td><span class="badge bg-' . 
                            match($rapport['statut']) {
                                'valide' => 'success',
                                'rejete' => 'danger',
                                default => 'warning'
                            } . '">' . 
                            ucfirst($rapport['statut']) . '</span></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Statistiques générales</h6>
                <table class="table table-sm">
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
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h6>Répartition des patients par sexe et âge</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tranche d\'âge</th>
                            <th>Femmes</th>
                            <th>Hommes</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
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
                        <tr class="table-info">
                            <td><strong>Total</strong></td>
                            <td><strong>' . 
                                ($rapport['femmes_3_12'] + $rapport['femmes_13_19'] + 
                                 $rapport['femmes_20_49'] + $rapport['femmes_50_plus']) . 
                            '</strong></td>
                            <td><strong>' . 
                                ($rapport['hommes_3_12'] + $rapport['hommes_13_19'] + 
                                 $rapport['hommes_20_49'] + $rapport['hommes_50_plus']) . 
                            '</strong></td>
                            <td><strong>' . $rapport['total_patients_dialyses'] . '</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';

    if ($rapport['commentaire_admin']) {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6>Commentaire de l\'administrateur</h6>
                    <p class="mb-0">' . nl2br(htmlspecialchars($rapport['commentaire_admin'])) . '</p>
                </div>
            </div>
        </div>';
    }

    $html .= '</div>';

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des détails : ' . $e->getMessage()
    ]);
} 