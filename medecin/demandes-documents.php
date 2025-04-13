<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer les statistiques des demandes
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as termines,
        SUM(CASE WHEN statut = 'refuse' THEN 1 ELSE 0 END) as refuses
    FROM demandes_documents
    WHERE id_medecin = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Récupérer les demandes avec filtres
$where_conditions = ["id_medecin = ?"];
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

// Récupérer la liste des patients pour le filtre
$stmt = $pdo->prepare("
    SELECT DISTINCT p.id_patient, p.nom, p.prenom
    FROM demandes_documents d
    JOIN patients p ON d.id_patient = p.id_patient
    WHERE d.id_medecin = ?
    ORDER BY p.nom, p.prenom
");
$stmt->execute([$_SESSION['user_id']]);
$patients = $stmt->fetchAll();

// Récupérer les demandes
$stmt = $pdo->prepare("
    SELECT d.*, p.nom as patient_nom, p.prenom as patient_prenom
    FROM demandes_documents d
    JOIN patients p ON d.id_patient = p.id_patient
    WHERE $where_clause
    ORDER BY d.date_creation DESC
");
$stmt->execute($params);
$demandes = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Gestion des demandes de documents</h1>
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
            <div class="card bg-info text-white">
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
                        <option value="refuse" <?php echo isset($_GET['statut']) && $_GET['statut'] === 'refuse' ? 'selected' : ''; ?>>Refusée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tous</option>
                        <option value="prescription" <?php echo isset($_GET['type']) && $_GET['type'] === 'prescription' ? 'selected' : ''; ?>>Prescription</option>
                        <option value="resultat" <?php echo isset($_GET['type']) && $_GET['type'] === 'resultat' ? 'selected' : ''; ?>>Résultat</option>
                        <option value="certificat" <?php echo isset($_GET['type']) && $_GET['type'] === 'certificat' ? 'selected' : ''; ?>>Certificat</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="patient" class="form-label">Patient</label>
                    <select class="form-select" id="patient" name="patient">
                        <option value="">Tous</option>
                        <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['id_patient']; ?>" <?php echo isset($_GET['patient']) && $_GET['patient'] == $patient['id_patient'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['prenom'] . ' ' . $patient['nom']); ?>
                        </option>
                        <?php endforeach; ?>
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
                    <a href="demandes-documents.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des demandes -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $demande): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($demande['date_creation'])); ?></td>
                            <td><?php echo htmlspecialchars($demande['patient_prenom'] . ' ' . $demande['patient_nom']); ?></td>
                            <td>
                                <?php
                                $type_class = match($demande['type']) {
                                    'prescription' => 'bg-success',
                                    'resultat' => 'bg-info',
                                    'certificat' => 'bg-warning',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $type_class; ?>">
                                    <?php echo ucfirst($demande['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($demande['titre']); ?></td>
                            <td><?php echo htmlspecialchars($demande['description']); ?></td>
                            <td>
                                <?php
                                $badge_class = match($demande['statut']) {
                                    'en_attente' => 'bg-warning',
                                    'en_cours' => 'bg-info',
                                    'termine' => 'bg-success',
                                    'refuse' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $demande['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($demande['statut'] === 'en_attente'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="accepterDemande(<?php echo $demande['id_demande']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="refuserDemande(<?php echo $demande['id_demande']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($demande['statut'] === 'en_cours'): ?>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="terminerDemande(<?php echo $demande['id_demande']; ?>)">
                                        <i class="fas fa-flag-checkered"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-info" onclick="voirDetails(<?php echo $demande['id_demande']; ?>)">
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
                <div id="commentaireField" class="mb-3" style="display: none;">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control" id="commentaire" rows="3"></textarea>
                </div>
                <div id="motifRefusField" class="mb-3" style="display: none;">
                    <label for="motif_refus" class="form-label">Motif du refus</label>
                    <textarea class="form-control" id="motif_refus" rows="3" required></textarea>
                </div>
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
                <h5 class="modal-title">Détails de la demande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="demandeDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAction = '';
let currentDemandeId = null;
const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

function accepterDemande(id) {
    currentAction = 'accepter';
    currentDemandeId = id;
    document.getElementById('commentaireField').style.display = 'block';
    document.getElementById('motifRefusField').style.display = 'none';
    confirmationModal.show();
}

function refuserDemande(id) {
    currentAction = 'refuser';
    currentDemandeId = id;
    document.getElementById('commentaireField').style.display = 'none';
    document.getElementById('motifRefusField').style.display = 'block';
    confirmationModal.show();
}

function terminerDemande(id) {
    currentAction = 'terminer';
    currentDemandeId = id;
    document.getElementById('commentaireField').style.display = 'block';
    document.getElementById('motifRefusField').style.display = 'none';
    confirmationModal.show();
}

function voirDetails(id) {
    currentDemandeId = id;
    // Charger les détails via AJAX
    fetch(`/santeplus/medecin/get-demande-details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('demandeDetails').innerHTML = `
                <p><strong>Patient :</strong> ${data.patient_nom} ${data.patient_prenom}</p>
                <p><strong>Type :</strong> ${data.type}</p>
                <p><strong>Titre :</strong> ${data.titre}</p>
                <p><strong>Description :</strong> ${data.description}</p>
                <p><strong>Statut :</strong> ${data.statut}</p>
                <p><strong>Date de création :</strong> ${new Date(data.date_creation).toLocaleString()}</p>
                <p><strong>Commentaire :</strong> ${data.commentaire || 'Aucun'}</p>
            `;
            detailsModal.show();
        });
}

document.getElementById('confirmAction').addEventListener('click', function() {
    if (!currentDemandeId) return;
    
    const formData = new FormData();
    formData.append('id_demande', currentDemandeId);
    formData.append('action', currentAction);
    
    if (currentAction === 'refuser') {
        const motifRefus = document.getElementById('motif_refus').value;
        if (!motifRefus) {
            alert('Veuillez indiquer le motif du refus.');
            return;
        }
        formData.append('motif_refus', motifRefus);
    }
    
    const commentaire = document.getElementById('commentaire').value;
    if (commentaire) {
        formData.append('commentaire', commentaire);
    }
    
    fetch('/santeplus/medecin/traiter-demande.php', {
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
    document.getElementById('commentaire').value = '';
    document.getElementById('motif_refus').value = '';
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