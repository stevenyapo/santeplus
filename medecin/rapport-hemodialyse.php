<?php
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
    WHERE r.id_rapport = ? AND r.id_medecin = ?
");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$rapport = $stmt->fetch();

// Si le rapport n'existe pas ou n'appartient pas au médecin
if (!$rapport) {
    header('Location: rapports.php');
    exit;
}

// Fonction pour afficher une valeur ou "Non renseigné" si elle est vide
function afficherValeur($valeur, $unite = '') {
    if (empty($valeur)) {
        return 'Non renseigné';
    }
    return htmlspecialchars($valeur) . ($unite ? ' ' . $unite : '');
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Rapport d'hémodialyse</h6>
                        <div>
                            <a href="rapports.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <?php if ($rapport['statut'] === 'en_attente'): ?>
                            <a href="modifier-rapport.php?id=<?php echo $rapport['id_rapport']; ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-2"></i>Modifier
                            </a>
                            <?php endif; ?>
                            <a href="supprimer-document.php?id=<?php echo $rapport['id_rapport']; ?>&type=rapport" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?')">
                                <i class="fas fa-trash me-2"></i>Supprimer
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Date de création</h6>
                                <p class="text-sm"><?php echo date('d/m/Y H:i', strtotime($rapport['date_creation'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Date du rapport</h6>
                                <p class="text-sm"><?php echo date('d/m/Y', strtotime($rapport['date_rapport'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Antenne</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['antenne']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Statut</h6>
                                <p class="text-sm">
                                    <span class="badge badge-sm bg-gradient-<?php 
                                        echo $rapport['statut'] === 'valide' ? 'success' : 
                                            ($rapport['statut'] === 'rejete' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($rapport['statut']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Nombre de générateurs fonctionnels</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['nb_generateurs_fonctionnels']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Nombre de postes de dialyse</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['nb_postes_dialyse']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Nombre de séances d'urgence</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['nb_seances_urgence']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Nombre de patients en séjour</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['nb_patients_sejour']); ?></p>
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
                                            <td><?php echo afficherValeur($rapport['femmes_3_12']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Femmes 13-19 ans</td>
                                            <td><?php echo afficherValeur($rapport['femmes_13_19']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Femmes 20-49 ans</td>
                                            <td><?php echo afficherValeur($rapport['femmes_20_49']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Femmes 50+ ans</td>
                                            <td><?php echo afficherValeur($rapport['femmes_50_plus']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Hommes 3-12 ans</td>
                                            <td><?php echo afficherValeur($rapport['hommes_3_12']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Hommes 13-19 ans</td>
                                            <td><?php echo afficherValeur($rapport['hommes_13_19']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Hommes 20-49 ans</td>
                                            <td><?php echo afficherValeur($rapport['hommes_20_49']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Hommes 50+ ans</td>
                                            <td><?php echo afficherValeur($rapport['hommes_50_plus']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Total patients dialysés</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['total_patients_dialyses']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Nombre de décès</h6>
                                <p class="text-sm"><?php echo afficherValeur($rapport['nb_deces']); ?></p>
                            </div>
                            <?php if (!empty($rapport['commentaire_admin'])): ?>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-secondary text-xs font-weight-bolder">Commentaire de l'administrateur</h6>
                                <p class="text-sm"><?php echo nl2br(htmlspecialchars($rapport['commentaire_admin'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 