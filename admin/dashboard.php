<?php
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer les statistiques
$stats = [
    'medecins' => $pdo->query("SELECT COUNT(*) FROM medecins")->fetchColumn(),
    'rapports' => $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse")->fetchColumn()
];

// Fetch last month's data
$last_month_medecins = $pdo->query("SELECT COUNT(*) FROM medecins WHERE MONTH(date_inscription) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND YEAR(date_inscription) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)")->fetchColumn();
$last_month_rapports = $pdo->query("SELECT COUNT(*) FROM rapports_hemodialyse WHERE MONTH(date_creation) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND YEAR(date_creation) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)")->fetchColumn();

// Calculate percentage changes
$medecins_change = $last_month_medecins > 0 ? (($stats['medecins'] - $last_month_medecins) / $last_month_medecins) * 100 : 0;
$rapports_change = $last_month_rapports > 0 ? (($stats['rapports'] - $last_month_rapports) / $last_month_rapports) * 100 : 0;

// Récupérer les derniers rapports
$derniers_rapports = $pdo->query("
    SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM rapports_hemodialyse r
    JOIN medecins m ON r.id_medecin = m.id_medecin
    ORDER BY r.date_creation DESC
    LIMIT 5
")->fetchAll();

// Fetch report activity data for the past week
$activity_data = $pdo->query("SELECT DATE(date_creation) as date, COUNT(*) as count FROM rapports_hemodialyse WHERE date_creation >= CURDATE() - INTERVAL 7 DAY GROUP BY DATE(date_creation)")->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare data for the chart
$activity_labels = [];
$activity_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $activity_labels[] = date('D', strtotime($date));
    $activity_counts[] = $activity_data[$date] ?? 0;
}

// Fetch recent notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY date_creation DESC LIMIT 2")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- En-tête avec les boutons de filtre -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Tableau de bord</h2>
        <div class="d-flex gap-2">
            <?php if (isset($_GET['periode']) || isset($_GET['date_debut']) || isset($_GET['date_fin'])): ?>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Effacer les filtres
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter me-2"></i>Filtrer
            </button>
        </div>
    </div>

    <!-- Affichage des filtres actifs -->
    <?php if (isset($_GET['periode']) || isset($_GET['date_debut']) || isset($_GET['date_fin'])): ?>
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                Filtres actifs : 
                <?php if (isset($_GET['periode'])): ?>
                    <span class="badge bg-secondary me-2">Période : <?php echo $_GET['periode'] === 'mois' ? 'Mensuelle' : 'Annuelle'; ?></span>
                <?php endif; ?>
                <?php if (isset($_GET['date_debut'])): ?>
                    <span class="badge bg-secondary me-2">Du : <?php echo date('d/m/Y', strtotime($_GET['date_debut'])); ?></span>
                <?php endif; ?>
                <?php if (isset($_GET['date_fin'])): ?>
                    <span class="badge bg-secondary me-2">Au : <?php echo date('d/m/Y', strtotime($_GET['date_fin'])); ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistiques principales -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted mb-2">Médecins</h6>
                            <h2 class="mb-0"><?php echo $stats['medecins']; ?></h2>
                        </div>
                        <div class="icon-shape bg-success bg-opacity-10 rounded-circle">
                            <i class="fas fa-user-md text-success"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i><?php echo round($medecins_change, 2); ?>%
                        </span>
                        <span class="text-muted ms-2">Depuis le mois dernier</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-muted mb-2">Rapports</h6>
                            <h2 class="mb-0"><?php echo $stats['rapports']; ?></h2>
                        </div>
                        <div class="icon-shape bg-warning bg-opacity-10 rounded-circle">
                            <i class="fas fa-file-medical text-warning"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success">
                            <i class="fas fa-arrow-up me-1"></i><?php echo round($rapports_change, 2); ?>%
                        </span>
                        <span class="text-muted ms-2">Depuis le mois dernier</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et tableaux -->
    <div class="row g-3 mb-4">
        <!-- Graphique d'activité -->
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activité des rapports</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derniers rapports -->
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Derniers rapports</h5>
                    <a href="rapports_hemodialyse.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Antenne</th>
                                    <th>Médecin</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_rapports as $rapport): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rapport['antenne']); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($rapport['date_creation'])); ?></td>
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

    <!-- Actions rapides et notifications -->
    <div class="row g-3">
        <!-- Actions rapides -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="gestion_comptes.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users-cog me-2"></i>Gérer les comptes
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="rapports_hemodialyse.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-medical me-2"></i>Rapports
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="btn btn-outline-primary w-100">
                                <i class="fas fa-calendar-check me-2"></i>Rendez-vous
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-12 col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Notifications</h5>
                    <button class="btn btn-sm btn-link">Marquer tout comme lu</button>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <i class="fas fa-bell text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></h6>
                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($notification['message'] ?? ''); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['date_creation'] ?? 'now')); ?></small>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- Modal de filtrage -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filtrer les données</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="filterForm" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="periode" class="form-label">Période</label>
                        <select class="form-select" id="periode" name="periode">
                            <option value="mois" <?php echo ($periode ?? 'mois') === 'mois' ? 'selected' : ''; ?>>Par mois</option>
                            <option value="annee" <?php echo ($periode ?? '') === 'annee' ? 'selected' : ''; ?>>Par année</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" 
                               value="<?php echo isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-01', strtotime('-11 months')); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" 
                               value="<?php echo isset($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration du graphique d'activité
    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($activity_labels); ?>,
            datasets: [{
                label: 'Rapports soumis',
                data: <?php echo json_encode($activity_counts); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });

    // Gestion du formulaire de filtrage
    const filterForm = document.getElementById('filterForm');
    const filterModal = new bootstrap.Modal(document.getElementById('filterModal'));

    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les valeurs du formulaire
            const periode = document.getElementById('periode').value;
            const dateDebut = document.getElementById('date_debut').value;
            const dateFin = document.getElementById('date_fin').value;
            
            // Construire l'URL avec les paramètres
            const params = new URLSearchParams(window.location.search);
            params.set('periode', periode);
            params.set('date_debut', dateDebut);
            params.set('date_fin', dateFin);
            
            // Fermer le modal
            filterModal.hide();
            
            // Rediriger avec les nouveaux paramètres
            window.location.href = window.location.pathname + '?' + params.toString();
        });
        
        // Validation des dates
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        
        dateDebut.addEventListener('change', function() {
            dateFin.min = this.value;
            if (dateFin.value && dateFin.value < this.value) {
                dateFin.value = this.value;
            }
        });
        
        dateFin.addEventListener('change', function() {
            dateDebut.max = this.value;
            if (dateDebut.value && dateDebut.value > this.value) {
                dateDebut.value = this.value;
            }
        });
    }
});
</script>

</body>
</html> 