<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer les statistiques
$stats = [
    'total_rapports' => $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse WHERE id_medecin = " . $_SESSION['user_id'])->fetchColumn(),
    'rapports_attente' => $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse WHERE id_medecin = " . $_SESSION['user_id'] . " AND statut = 'en_attente'")->fetchColumn(),
    'rapports_valides' => $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse WHERE id_medecin = " . $_SESSION['user_id'] . " AND statut = 'valide'")->fetchColumn(),
    'rapports_rejetes' => $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse WHERE id_medecin = " . $_SESSION['user_id'] . " AND statut = 'rejete'")->fetchColumn()
];

// Récupérer les 5 derniers rapports
$stmt = $pdo->prepare("
    SELECT * FROM rapports_hemodialyse 
    WHERE id_medecin = ? 
    ORDER BY date_creation DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$derniers_rapports = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Statistiques -->
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Rapports</p>
                                <h5 class="font-weight-bolder"><?php echo $stats['total_rapports']; ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                <i class="fas fa-file-medical text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">En Attente</p>
                                <h5 class="font-weight-bolder"><?php echo $stats['rapports_attente']; ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                                <i class="fas fa-clock text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Validés</p>
                                <h5 class="font-weight-bolder"><?php echo $stats['rapports_valides']; ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                <i class="fas fa-check text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Rejetés</p>
                                <h5 class="font-weight-bolder"><?php echo $stats['rapports_rejetes']; ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                                <i class="fas fa-times text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Actions Rapides -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Actions Rapides</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="hemodialyse.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nouveau Rapport
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derniers Rapports -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6>Derniers Rapports</h6>
                        </div>
                        <div class="col-auto">
                            <a href="rapports.php" class="btn btn-sm btn-primary">Voir tout</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Antenne</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_rapports as $rapport): ?>
                                <tr>
                                    <td>
                                        <span class="text-xs font-weight-bold"><?php echo date('d/m/Y', strtotime($rapport['date_rapport'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-xs font-weight-bold"><?php echo htmlspecialchars($rapport['antenne']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-gradient-<?php 
                                            echo $rapport['statut'] === 'valide' ? 'success' : 
                                                ($rapport['statut'] === 'rejete' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($rapport['statut']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="rapport-hemodialyse.php?id=<?php echo $rapport['id_rapport']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($rapport['statut'] === 'en_attente'): ?>
                                        <a href="modifier-rapport.php?id=<?php echo $rapport['id_rapport']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
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

<?php require_once '../includes/footer.php'; ?> 