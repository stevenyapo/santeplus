<?php
ob_start(); // Démarre le buffer de sortie
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Vérifier si l'ID du rapport est fourni
if (!isset($_GET['id'])) {
    header('Location: rapports.php');
    exit;
}

// Récupérer les détails du rapport
$stmt = $pdo->prepare("
    SELECT r.*
    FROM rapports_hemodialyse r
    WHERE r.id_rapport = ? AND r.id_medecin = ? AND r.statut = 'en_attente'
");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$rapport = $stmt->fetch();

// Si le rapport n'existe pas, n'appartient pas au médecin ou n'est pas en attente
if (!$rapport) {
    header('Location: rapports.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE rapports_hemodialyse 
            SET 
                antenne = ?,
                date_rapport = ?,
                femmes_3_12 = ?,
                femmes_13_19 = ?,
                femmes_20_49 = ?,
                femmes_50_plus = ?,
                hommes_3_12 = ?,
                hommes_13_19 = ?,
                hommes_20_49 = ?,
                hommes_50_plus = ?,
                total_patients_dialyses = ?,
                nb_generateurs_fonctionnels = ?,
                nb_postes_dialyse = ?,
                nb_seances_urgence = ?,
                nb_patients_sejour = ?,
                nb_deces = ?
            WHERE id_rapport = ? AND id_medecin = ? AND statut = 'en_attente'
        ");

        $stmt->execute([
            $_POST['antenne'],
            $_POST['date_rapport'],
            $_POST['femmes_3_12'],
            $_POST['femmes_13_19'],
            $_POST['femmes_20_49'],
            $_POST['femmes_50_plus'],
            $_POST['hommes_3_12'],
            $_POST['hommes_13_19'],
            $_POST['hommes_20_49'],
            $_POST['hommes_50_plus'],
            $_POST['total_patients_dialyses'],
            $_POST['nb_generateurs_fonctionnels'],
            $_POST['nb_postes_dialyse'],
            $_POST['nb_seances_urgence'],
            $_POST['nb_patients_sejour'],
            $_POST['nb_deces'],
            $rapport['id_rapport'],
            $_SESSION['user_id']
        ]);

        $_SESSION['success'] = "Le rapport a été modifié avec succès.";
        header('Location: rapport-hemodialyse.php?id=' . $rapport['id_rapport']);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue lors de la modification du rapport.";
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Modifier le rapport d'hémodialyse</h6>
                        <a href="rapport-hemodialyse.php?id=<?php echo $rapport['id_rapport']; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date du rapport</label>
                                    <input type="date" class="form-control" name="date_rapport" value="<?php echo htmlspecialchars($rapport['date_rapport']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Antenne</label>
                                    <input type="text" class="form-control" name="antenne" value="<?php echo htmlspecialchars($rapport['antenne']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de générateurs fonctionnels</label>
                                    <input type="number" class="form-control" name="nb_generateurs_fonctionnels" value="<?php echo htmlspecialchars($rapport['nb_generateurs_fonctionnels']); ?>" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de postes de dialyse</label>
                                    <input type="number" class="form-control" name="nb_postes_dialyse" value="<?php echo htmlspecialchars($rapport['nb_postes_dialyse']); ?>" required min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Répartition par âge et sexe</h6>
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Catégorie</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nombre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Femmes 3-12 ans</td>
                                                <td><input type="number" class="form-control" name="femmes_3_12" value="<?php echo htmlspecialchars($rapport['femmes_3_12']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Femmes 13-19 ans</td>
                                                <td><input type="number" class="form-control" name="femmes_13_19" value="<?php echo htmlspecialchars($rapport['femmes_13_19']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Femmes 20-49 ans</td>
                                                <td><input type="number" class="form-control" name="femmes_20_49" value="<?php echo htmlspecialchars($rapport['femmes_20_49']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Femmes 50+ ans</td>
                                                <td><input type="number" class="form-control" name="femmes_50_plus" value="<?php echo htmlspecialchars($rapport['femmes_50_plus']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Hommes 3-12 ans</td>
                                                <td><input type="number" class="form-control" name="hommes_3_12" value="<?php echo htmlspecialchars($rapport['hommes_3_12']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Hommes 13-19 ans</td>
                                                <td><input type="number" class="form-control" name="hommes_13_19" value="<?php echo htmlspecialchars($rapport['hommes_13_19']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Hommes 20-49 ans</td>
                                                <td><input type="number" class="form-control" name="hommes_20_49" value="<?php echo htmlspecialchars($rapport['hommes_20_49']); ?>" required min="0"></td>
                                            </tr>
                                            <tr>
                                                <td>Hommes 50+ ans</td>
                                                <td><input type="number" class="form-control" name="hommes_50_plus" value="<?php echo htmlspecialchars($rapport['hommes_50_plus']); ?>" required min="0"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total patients dialysés</label>
                                    <input type="number" class="form-control" name="total_patients_dialyses" value="<?php echo htmlspecialchars($rapport['total_patients_dialyses']); ?>" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de séances d'urgence</label>
                                    <input type="number" class="form-control" name="nb_seances_urgence" value="<?php echo htmlspecialchars($rapport['nb_seances_urgence']); ?>" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de patients en séjour</label>
                                    <input type="number" class="form-control" name="nb_patients_sejour" value="<?php echo htmlspecialchars($rapport['nb_patients_sejour']); ?>" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de décès</label>
                                    <input type="number" class="form-control" name="nb_deces" value="<?php echo htmlspecialchars($rapport['nb_deces']); ?>" required min="0">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 