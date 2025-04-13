<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Récupérer les statistiques des documents
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'prescription' THEN 1 ELSE 0 END) as prescriptions,
        SUM(CASE WHEN type = 'resultat' THEN 1 ELSE 0 END) as resultats,
        SUM(CASE WHEN type = 'certificat' THEN 1 ELSE 0 END) as certificats
    FROM documents_medicaux
    WHERE id_medecin = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Construire la requête de filtrage
$where_conditions = ['id_medecin = ?'];
$params = [$_SESSION['user_id']];

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_conditions[] = 'type = ?';
    $params[] = cleanInput($_GET['type']);
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $where_conditions[] = 'date_creation >= ?';
    $params[] = cleanInput($_GET['date_debut']);
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $where_conditions[] = 'date_creation <= ?';
    $params[] = cleanInput($_GET['date_fin']);
}

// Récupérer les documents
$sql = "
    SELECT d.*, p.nom as patient_nom, p.prenom as patient_prenom, u.email as patient_email
    FROM documents_medicaux d
    JOIN patients p ON d.id_patient = p.id_patient
    JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY d.date_creation DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Documents médicaux</h1>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total</h5>
                    <p class="card-text display-6"><?php echo $stats['total']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Prescriptions</h5>
                    <p class="card-text display-6"><?php echo $stats['prescriptions']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Résultats</h5>
                    <p class="card-text display-6"><?php echo $stats['resultats']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Certificats</h5>
                    <p class="card-text display-6"><?php echo $stats['certificats']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Type de document</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Tous les types</option>
                        <option value="prescription" <?php echo isset($_GET['type']) && $_GET['type'] === 'prescription' ? 'selected' : ''; ?>>Prescription</option>
                        <option value="resultat" <?php echo isset($_GET['type']) && $_GET['type'] === 'resultat' ? 'selected' : ''; ?>>Résultat</option>
                        <option value="certificat" <?php echo isset($_GET['type']) && $_GET['type'] === 'certificat' ? 'selected' : ''; ?>>Certificat</option>
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
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="documents-medicaux.php" class="btn btn-secondary">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des documents -->
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($document['date_creation'])); ?></td>
                            <td><?php echo htmlspecialchars($document['patient_prenom'] . ' ' . $document['patient_nom']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getTypeBadgeColor($document['type']); ?>">
                                    <?php echo ucfirst($document['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($document['titre']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" onclick="voirDetails(<?php echo $document['id_document']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="/santeplus/uploads/documents/<?php echo $document['chemin_fichier']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="supprimerDocument(<?php echo $document['id_document']; ?>)">
                                        <i class="fas fa-trash"></i>
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

<!-- Modal de détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div id="detailsModalContent"></div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce document ? Cette action est irréversible.</p>
                <form id="deleteForm" method="POST" action="supprimer-document.php">
                    <input type="hidden" name="id_document" id="deleteDocumentId">
                    <div class="mb-3">
                        <label for="motif_suppression" class="form-label">Motif de la suppression</label>
                        <textarea class="form-control" id="motif_suppression" name="motif_suppression" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="deleteForm" class="btn btn-danger">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour voir les détails d'un document
function voirDetails(id) {
    fetch(`get-document-details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('detailsModalContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la récupération des détails.');
        });
}

// Fonction pour supprimer un document
function supprimerDocument(id) {
    document.getElementById('deleteDocumentId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Validation de la date de fin
document.getElementById('date_fin').addEventListener('change', function() {
    const dateDebut = document.getElementById('date_debut').value;
    const dateFin = this.value;
    
    if (dateDebut && dateFin && dateFin < dateDebut) {
        alert('La date de fin doit être postérieure à la date de début.');
        this.value = '';
    }
});
</script>

<?php
// Fonction pour déterminer la couleur du badge selon le type de document
function getTypeBadgeColor($type) {
    switch ($type) {
        case 'prescription':
            return 'success';
        case 'resultat':
            return 'info';
        case 'certificat':
            return 'warning';
        default:
            return 'secondary';
    }
}

require_once '../includes/footer.php';
?> 