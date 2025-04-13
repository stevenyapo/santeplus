<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer les statistiques des urgences
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
        SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annules
    FROM urgences
    WHERE id_medecin = ? OR id_medecin IS NULL
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Récupérer les urgences avec filtres
$where_conditions = ["(id_medecin = ? OR id_medecin IS NULL)"];
$params = [$_SESSION['user_id']];

if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $where_conditions[] = "statut = ?";
    $params[] = cleanInput($_GET['statut']);
}

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_conditions[] = "type = ?";
    $params[] = cleanInput($_GET['type']);
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $where_conditions[] = "date_creation >= ?";
    $params[] = cleanInput($_GET['date_debut']);
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $where_conditions[] = "date_creation <= ?";
    $params[] = cleanInput($_GET['date_fin']);
}

$where_clause = implode(" AND ", $where_conditions);

$stmt = $pdo->prepare("
    SELECT u.*, p.nom as patient_nom, p.prenom as patient_prenom, u.email as patient_email,
           m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM urgences u
    JOIN patients p ON u.id_patient = p.id_patient
    JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
    LEFT JOIN medecins m ON u.id_medecin = m.id_medecin
    WHERE $where_clause
    ORDER BY u.date_creation DESC
");
$stmt->execute($params);
$urgences = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Gestion des urgences</h1>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <h2 class="card-text"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">En attente</h5>
                    <h2 class="card-text"><?php echo $stats['en_attente']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">En cours</h5>
                    <h2 class="card-text"><?php echo $stats['en_cours']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Terminées</h5>
                    <h2 class="card-text"><?php echo $stats['termines']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="">Tous</option>
                        <option value="en_attente" <?php echo isset($_GET['statut']) && $_GET['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="en_cours" <?php echo isset($_GET['statut']) && $_GET['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="termine" <?php echo isset($_GET['statut']) && $_GET['statut'] === 'termine' ? 'selected' : ''; ?>>Terminée</option>
                        <option value="annule" <?php echo isset($_GET['statut']) && $_GET['statut'] === 'annule' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tous</option>
                        <option value="medical" <?php echo isset($_GET['type']) && $_GET['type'] === 'medical' ? 'selected' : ''; ?>>Médical</option>
                        <option value="accident" <?php echo isset($_GET['type']) && $_GET['type'] === 'accident' ? 'selected' : ''; ?>>Accident</option>
                        <option value="autre" <?php echo isset($_GET['type']) && $_GET['type'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo isset($_GET['date_debut']) ? $_GET['date_debut'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo isset($_GET['date_fin']) ? $_GET['date_fin'] : ''; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                    <a href="urgences.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des urgences -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Localisation</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($urgences as $urgence): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($urgence['date_creation'])); ?></td>
                            <td><?php echo htmlspecialchars($urgence['patient_prenom'] . ' ' . $urgence['patient_nom']); ?></td>
                            <td>
                                <?php
                                $type_class = match($urgence['type']) {
                                    'medical' => 'bg-danger',
                                    'accident' => 'bg-warning',
                                    'autre' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $type_class; ?>">
                                    <?php echo ucfirst($urgence['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($urgence['description']); ?></td>
                            <td><?php echo htmlspecialchars($urgence['localisation']); ?></td>
                            <td>
                                <?php
                                $badge_class = match($urgence['statut']) {
                                    'en_attente' => 'bg-warning',
                                    'en_cours' => 'bg-danger',
                                    'termine' => 'bg-success',
                                    'annule' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $urgence['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($urgence['statut'] === 'en_attente'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="prendreEnCharge(<?php echo $urgence['id_urgence']; ?>)">
                                        <i class="fas fa-hand-holding-medical"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($urgence['statut'] === 'en_cours'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="terminerUrgence(<?php echo $urgence['id_urgence']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-info" onclick="voirDetails(<?php echo $urgence['id_urgence']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir effectuer cette action ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'urgence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="urgenceDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAction = '';
let currentUrgenceId = null;
const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

function prendreEnCharge(id) {
    currentAction = 'prendre_en_charge';
    currentUrgenceId = id;
    confirmationModal.show();
}

function terminerUrgence(id) {
    currentAction = 'terminer';
    currentUrgenceId = id;
    confirmationModal.show();
}

function voirDetails(id) {
    currentUrgenceId = id;
    // Charger les détails via AJAX
    fetch(`/santeplus/medecin/get-urgence-details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('urgenceDetails').innerHTML = `
                <p><strong>Patient :</strong> ${data.patient_nom} ${data.patient_prenom}</p>
                <p><strong>Email :</strong> ${data.patient_email}</p>
                <p><strong>Date :</strong> ${new Date(data.date_creation).toLocaleString()}</p>
                <p><strong>Type :</strong> ${data.type}</p>
                <p><strong>Description :</strong> ${data.description}</p>
                <p><strong>Localisation :</strong> ${data.localisation}</p>
                <p><strong>Statut :</strong> ${data.statut}</p>
                <p><strong>Notes :</strong> ${data.notes || 'Aucune'}</p>
            `;
            detailsModal.show();
        });
}

document.getElementById('confirmAction').addEventListener('click', function() {
    if (!currentUrgenceId) return;
    
    const formData = new FormData();
    formData.append('id_urgence', currentUrgenceId);
    formData.append('action', currentAction);
    
    fetch('/santeplus/medecin/traiter-urgence.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue.');
        }
    });
    
    confirmationModal.hide();
});

// Validation des dates
document.querySelector('form').addEventListener('submit', function(e) {
    const dateDebut = document.getElementById('date_debut').value;
    const dateFin = document.getElementById('date_fin').value;
    
    if (dateDebut && dateFin && new Date(dateDebut) > new Date(dateFin)) {
        e.preventDefault();
        alert('La date de début doit être antérieure à la date de fin.');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 