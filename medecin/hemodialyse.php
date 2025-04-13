<?php
ob_start(); // Démarre le buffer de sortie

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/header.php';

// Logs de débogage
error_log("=== Debug Session Hemodialyse ===");
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    error_log("Utilisateur non connecté ou non médecin, redirection vers login.php");
    $_SESSION['error_message'] = "Vous devez être connecté en tant que médecin pour accéder à cette page.";
    header('Location: /santeplus/login.php');
    exit();
}

$id_medecin = $_SESSION['user_id'];

// Récupérer l'antenne du médecin
$stmt = $pdo->prepare("SELECT antenne FROM medecins WHERE id_medecin = ?");
$stmt->execute([$id_medecin]);
$antenne = $stmt->fetchColumn();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $required_fields = [
            'date_rapport',
            'femmes_3_12', 'femmes_13_19', 'femmes_20_49', 'femmes_50_plus',
            'hommes_3_12', 'hommes_13_19', 'hommes_20_49', 'hommes_50_plus',
            'nb_generateurs_fonctionnels', 'nb_postes_dialyse',
            'nb_seances_urgence', 'nb_patients_sejour', 'nb_deces'
        ];

        $errors = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "Le champ " . str_replace('_', ' ', $field) . " est requis.";
            }
        }

        // Vérifier que les valeurs sont des nombres positifs
        foreach ($_POST as $key => $value) {
            if ($key !== 'date_rapport' && (!is_numeric($value) || $value < 0)) {
                $errors[] = "La valeur de " . str_replace('_', ' ', $key) . " doit être un nombre positif.";
            }
        }

        if (empty($errors)) {
            // Calculer le total des patients
            $total_patients = 
                $_POST['femmes_3_12'] + $_POST['femmes_13_19'] + 
                $_POST['femmes_20_49'] + $_POST['femmes_50_plus'] +
                $_POST['hommes_3_12'] + $_POST['hommes_13_19'] + 
                $_POST['hommes_20_49'] + $_POST['hommes_50_plus'];

            $stmt = $pdo->prepare("
                INSERT INTO rapports_hemodialyse (
                    id_medecin, antenne, date_rapport,
                    femmes_3_12, femmes_13_19, femmes_20_49, femmes_50_plus,
                    hommes_3_12, hommes_13_19, hommes_20_49, hommes_50_plus,
                    total_patients_dialyses,
                    nb_generateurs_fonctionnels, nb_postes_dialyse,
                    nb_seances_urgence, nb_patients_sejour, nb_deces,
                    statut
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?,
                    ?, ?,
                    ?, ?, ?,
                    'en_attente'
                )
            ");

            $stmt->execute([
                $id_medecin,
                $antenne,
                $_POST['date_rapport'],
                $_POST['femmes_3_12'],
                $_POST['femmes_13_19'],
                $_POST['femmes_20_49'],
                $_POST['femmes_50_plus'],
                $_POST['hommes_3_12'],
                $_POST['hommes_13_19'],
                $_POST['hommes_20_49'],
                $_POST['hommes_50_plus'],
                $total_patients,
                $_POST['nb_generateurs_fonctionnels'],
                $_POST['nb_postes_dialyse'],
                $_POST['nb_seances_urgence'],
                $_POST['nb_patients_sejour'],
                $_POST['nb_deces']
            ]);

            // Envoyer une notification à l'administrateur
            $stmt = $pdo->prepare("
                INSERT INTO notifications (message, type_notification, lien, type_destinataire, destinataire_id)
                VALUES (?, 'info', ?, 'admin', 1)
            ");
            $stmt->execute([
                "Un nouveau rapport d'hémodialyse a été soumis par le Dr. " . $_SESSION['prenom'] . ' ' . $_SESSION['nom'] . " pour l'antenne " . $antenne,
                "admin/rapports_hemodialyse.php"
            ]);

            $_SESSION['success_message'] = "Le rapport a été soumis avec succès.";
            header('Location: hemodialyse.php');
            exit();
        }
    } catch (Exception $e) {
        $errors[] = "Erreur lors de la soumission du rapport : " . $e->getMessage();
    }
}

// Récupérer les rapports précédents
$stmt = $pdo->prepare("
    SELECT * FROM rapports_hemodialyse 
    WHERE id_medecin = ?
    ORDER BY date_creation DESC
");
$stmt->execute([$id_medecin]);
$rapports = $stmt->fetchAll();

?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Nouveau rapport d'hémodialyse</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="rapportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_rapport">Date du rapport</label>
                                    <input type="date" class="form-control" id="date_rapport" name="date_rapport" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Antenne</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($antenne); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4">Répartition des patients par sexe et âge</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Femmes</h6>
                                <div class="form-group">
                                    <label for="femmes_3_12">3-12 ans</label>
                                    <input type="number" class="form-control patient-count" id="femmes_3_12" name="femmes_3_12" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="femmes_13_19">13-19 ans</label>
                                    <input type="number" class="form-control patient-count" id="femmes_13_19" name="femmes_13_19" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="femmes_20_49">20-49 ans</label>
                                    <input type="number" class="form-control patient-count" id="femmes_20_49" name="femmes_20_49" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="femmes_50_plus">50 ans et plus</label>
                                    <input type="number" class="form-control patient-count" id="femmes_50_plus" name="femmes_50_plus" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Hommes</h6>
                                <div class="form-group">
                                    <label for="hommes_3_12">3-12 ans</label>
                                    <input type="number" class="form-control patient-count" id="hommes_3_12" name="hommes_3_12" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="hommes_13_19">13-19 ans</label>
                                    <input type="number" class="form-control patient-count" id="hommes_13_19" name="hommes_13_19" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="hommes_20_49">20-49 ans</label>
                                    <input type="number" class="form-control patient-count" id="hommes_20_49" name="hommes_20_49" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="hommes_50_plus">50 ans et plus</label>
                                    <input type="number" class="form-control patient-count" id="hommes_50_plus" name="hommes_50_plus" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>Équipement et capacité</h6>
                                <div class="form-group">
                                    <label for="nb_generateurs_fonctionnels">Nombre de générateurs fonctionnels</label>
                                    <input type="number" class="form-control" id="nb_generateurs_fonctionnels" name="nb_generateurs_fonctionnels" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="nb_postes_dialyse">Nombre de postes de dialyse</label>
                                    <input type="number" class="form-control" id="nb_postes_dialyse" name="nb_postes_dialyse" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Autres statistiques</h6>
                                <div class="form-group">
                                    <label for="nb_seances_urgence">Nombre de séances d'urgence</label>
                                    <input type="number" class="form-control" id="nb_seances_urgence" name="nb_seances_urgence" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="nb_patients_sejour">Nombre de patients en séjour</label>
                                    <input type="number" class="form-control" id="nb_patients_sejour" name="nb_patients_sejour" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="nb_deces">Nombre de décès</label>
                                    <input type="number" class="form-control" id="nb_deces" name="nb_deces" min="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">Soumettre le rapport</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Rapports précédents</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total patients</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapports as $rapport): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($rapport['date_rapport'])); ?></td>
                                        <td><?php echo $rapport['total_patients_dialyses']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($rapport['statut']) {
                                                    'valide' => 'success',
                                                    'rejete' => 'danger',
                                                    default => 'warning'
                                                };
                                            ?>">
                                                <?php echo ucfirst($rapport['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Définir la date du jour comme valeur par défaut
    document.getElementById('date_rapport').valueAsDate = new Date();

    // Initialiser tous les champs numériques à 0
    document.querySelectorAll('input[type="number"]').forEach(function(input) {
        input.value = '0';
    });

    // Mettre à jour le total des patients automatiquement
    document.querySelectorAll('.patient-count').forEach(function(input) {
        input.addEventListener('change', function() {
            let total = 0;
            document.querySelectorAll('.patient-count').forEach(function(input) {
                total += parseInt(input.value) || 0;
            });
            document.getElementById('total_patients').value = total;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 