<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du rapport non spécifié']);
    exit();
}

try {
    // Récupérer les détails du rapport
    $stmt = $pdo->prepare("
        SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
        FROM rapports_hemodialyse r
        JOIN medecins m ON r.id_medecin = m.id_medecin
        WHERE r.id_rapport = ?
    ");
    $stmt->execute([$_GET['id']]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rapport) {
        echo json_encode(['success' => false, 'message' => 'Rapport non trouvé']);
        exit();
    }

    // Préparer le HTML pour afficher les détails
    $html = "
        <div class='table-responsive'>
            <table class='table table-bordered'>
                <tr>
                    <th>Date du rapport</th>
                    <td>" . date('d/m/Y', strtotime($rapport['date_rapport'])) . "</td>
                    <th>Antenne</th>
                    <td>" . htmlspecialchars($rapport['antenne']) . "</td>
                </tr>
                <tr>
                    <th>Médecin</th>
                    <td>" . htmlspecialchars($rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom']) . "</td>
                    <th>Statut</th>
                    <td><span class='badge bg-" . 
                        match($rapport['statut']) {
                            'valide' => 'success',
                            'rejete' => 'danger',
                            default => 'warning'
                        } . "'>" . ucfirst($rapport['statut']) . "</span></td>
                </tr>
                <tr>
                    <th>Générateurs fonctionnels</th>
                    <td>" . $rapport['generateurs_fonctionnels'] . "</td>
                    <th>Postes de dialyse</th>
                    <td>" . $rapport['postes_dialyse'] . "</td>
                </tr>
                <tr>
                    <th colspan='4' class='text-center bg-light'>Répartition par âge</th>
                </tr>
                <tr>
                    <th>Moins de 15 ans</th>
                    <td>" . $rapport['moins_15_ans'] . "</td>
                    <th>15-25 ans</th>
                    <td>" . $rapport['age_15_25'] . "</td>
                </tr>
                <tr>
                    <th>25-35 ans</th>
                    <td>" . $rapport['age_25_35'] . "</td>
                    <th>35-45 ans</th>
                    <td>" . $rapport['age_35_45'] . "</td>
                </tr>
                <tr>
                    <th>45-65 ans</th>
                    <td>" . $rapport['age_45_65'] . "</td>
                    <th>Plus de 65 ans</th>
                    <td>" . $rapport['plus_65_ans'] . "</td>
                </tr>
                <tr>
                    <th colspan='4' class='text-center bg-light'>Répartition par genre</th>
                </tr>
                <tr>
                    <th>Hommes</th>
                    <td>" . $rapport['hommes'] . "</td>
                    <th>Femmes</th>
                    <td>" . $rapport['femmes'] . "</td>
                </tr>
                <tr>
                    <th colspan='4' class='text-center bg-light'>Statistiques générales</th>
                </tr>
                <tr>
                    <th>Total patients dialysés</th>
                    <td>" . $rapport['total_patients_dialyses'] . "</td>
                    <th>Séances d'urgence</th>
                    <td>" . $rapport['seances_urgence'] . "</td>
                </tr>
                <tr>
                    <th>Patients en séjour</th>
                    <td>" . $rapport['patients_sejour'] . "</td>
                    <th>Décès</th>
                    <td>" . $rapport['deces'] . "</td>
                </tr>";

    if (!empty($rapport['commentaire_admin'])) {
        $html .= "
            <tr>
                <th colspan='4' class='text-center bg-light'>Commentaire administrateur</th>
            </tr>
            <tr>
                <td colspan='4'>" . nl2br(htmlspecialchars($rapport['commentaire_admin'])) . "</td>
            </tr>";
    }

    $html .= "
            </table>
        </div>";

    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails : ' . $e->getMessage()]);
} 