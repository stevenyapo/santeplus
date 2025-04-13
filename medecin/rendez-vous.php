<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer les statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'confirme' THEN 1 ELSE 0 END) as confirmes,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
        SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annules
    FROM rendez_vous
    WHERE id_medecin = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Filtres
$filtre_statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
$filtre_date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$filtre_date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';

// Construire la requête
$sql = "
    SELECT r.*, p.nom as patient_nom, p.prenom as patient_prenom, p.telephone
    FROM rendez_vous r
    JOIN patients p ON r.id_patient = p.id_utilisateur
    WHERE r.id_medecin = ?
";

$params = [$_SESSION['user_id']];

if ($filtre_statut) {
    $sql .= " AND r.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_date_debut) {
    $sql .= " AND r.date_rdv >= ?";
    $params[] = $filtre_date_debut . ' 00:00:00';
}

if ($filtre_date_fin) {
    $sql .= " AND r.date_rdv <= ?";
    $params[] = $filtre_date_fin . ' 23:59:59';
}

$sql .= " ORDER BY r.date_rdv DESC";

// Exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rendez_vous = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-5">Gestion des Rendez-vous</h1>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">En attente</h5>
                    <h2 class="mb-0"><?php echo $stats['en_attente']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Confirmés</h5>
                    <h2 class="mb-0"><?php echo $stats['confirmes']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Terminés</h5>
                    <h2 class="mb-0"><?php echo $stats['termines']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtres
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="">Tous</option>
                        <option value="en_attente" <?php echo $filtre_statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="confirme" <?php echo $filtre_statut === 'confirme' ? 'selected' : ''; ?>>Confirmé</option>
                        <option value="termine" <?php echo $filtre_statut === 'termine' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="annule" <?php echo $filtre_statut === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $filtre_date_debut; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $filtre_date_fin; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Filtrer
                    </button>
                    <a href="/santeplus/medecin/rendez-vous.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-2"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des rendez-vous -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Liste des rendez-vous
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($rendez_vous)): ?>
                <p class="text-muted mb-0">Aucun rendez-vous trouvé.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendez_vous as $rdv): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($rdv['date_rdv'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($rdv['telephone']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($rdv['motif']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($rdv['statut']) {
                                                'confirme' => 'success',
                                                'en_attente' => 'warning',
                                                'annule' => 'danger',
                                                'termine' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($rdv['statut']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/santeplus/medecin/traiter-rdv.php?id=<?php echo $rdv['id_rdv']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 