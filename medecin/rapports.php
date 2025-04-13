<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Paramètres de pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Paramètres de filtrage
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Paramètres de tri
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_creation';
$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';

// Construire la requête SQL
$where = ['id_medecin = ?'];
$params = [$_SESSION['user_id']];

if ($statut) {
    $where[] = 'statut = ?';
    $params[] = $statut;
}

if ($date_debut) {
    $where[] = 'date_rapport >= ?';
    $params[] = $date_debut;
}

if ($date_fin) {
    $where[] = 'date_rapport <= ?';
    $params[] = $date_fin;
}

$where_clause = implode(' AND ', $where);

// Récupérer le nombre total de rapports
$count_sql = "SELECT COUNT(*) FROM rapports_hemodialyse WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rapports = $count_stmt->fetchColumn();
$total_pages = ceil($total_rapports / $per_page);

// Récupérer les rapports
$sql = "SELECT * FROM rapports_hemodialyse WHERE $where_clause ORDER BY $sort $order LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rapports = $stmt->fetchAll();

// Fonction pour générer l'URL avec les paramètres
function buildUrl($params) {
    $current_params = $_GET;
    foreach ($params as $key => $value) {
        $current_params[$key] = $value;
    }
    return '?' . http_build_query($current_params);
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6>Rapports d'hémodialyse</h6>
                        </div>
                        <div class="col-auto">
                            <a href="nouveau-rapport.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Nouveau Rapport
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Statut</label>
                                    <select name="statut" class="form-select">
                                        <option value="">Tous les statuts</option>
                                        <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="valide" <?php echo $statut === 'valide' ? 'selected' : ''; ?>>Validé</option>
                                        <option value="rejete" <?php echo $statut === 'rejete' ? 'selected' : ''; ?>>Rejeté</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date début</label>
                                    <input type="date" name="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date fin</label>
                                    <input type="date" name="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter me-2"></i>Filtrer
                                    </button>
                                    <a href="rapports.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Réinitialiser
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau des rapports -->
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        <a href="<?php echo buildUrl(['sort' => 'date_rapport', 'order' => $sort === 'date_rapport' && $order === 'ASC' ? 'desc' : 'asc']); ?>" class="text-secondary">
                                            Date
                                            <?php if ($sort === 'date_rapport'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        <a href="<?php echo buildUrl(['sort' => 'antenne', 'order' => $sort === 'antenne' && $order === 'ASC' ? 'desc' : 'asc']); ?>" class="text-secondary">
                                            Antenne
                                            <?php if ($sort === 'antenne'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        <a href="<?php echo buildUrl(['sort' => 'total_patients_dialyses', 'order' => $sort === 'total_patients_dialyses' && $order === 'ASC' ? 'desc' : 'asc']); ?>" class="text-secondary">
                                            Patients
                                            <?php if ($sort === 'total_patients_dialyses'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        <a href="<?php echo buildUrl(['sort' => 'statut', 'order' => $sort === 'statut' && $order === 'ASC' ? 'desc' : 'asc']); ?>" class="text-secondary">
                                            Statut
                                            <?php if ($sort === 'statut'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rapports)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun rapport trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rapports as $rapport): ?>
                                        <tr>
                                            <td>
                                                <span class="text-xs font-weight-bold"><?php echo date('d/m/Y', strtotime($rapport['date_rapport'])); ?></span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-weight-bold"><?php echo htmlspecialchars($rapport['antenne']); ?></span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-weight-bold"><?php echo $rapport['total_patients_dialyses']; ?></span>
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
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildUrl(['page' => $page - 1]); ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildUrl(['page' => $page + 1]); ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>