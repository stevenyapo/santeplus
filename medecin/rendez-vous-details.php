<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Vérifier si l'ID du rendez-vous est fourni
if (!isset($_GET['id'])) {
    header('Location: rendez-vous.php');
    exit;
}

$rdv_id = (int)$_GET['id'];

try {
    // Récupérer les détails du rendez-vous
    $stmt = $pdo->prepare("
        SELECT r.*, 
               p.nom as patient_nom, 
               p.prenom as patient_prenom,
               p.telephone as patient_telephone,
               p.date_naissance as patient_date_naissance,
               u.email as patient_email
        FROM rendez_vous r
        JOIN patients p ON r.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE r.id_rdv = ? AND r.id_medecin = ?
    ");
    $stmt->execute([$rdv_id, $_SESSION['user_id']]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        throw new Exception('Rendez-vous non trouvé');
    }

    // Récupérer l'historique des rendez-vous du patient
    $stmt = $pdo->prepare("
        SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
        FROM rendez_vous r
        JOIN medecins m ON r.id_medecin = m.id_medecin
        WHERE r.id_patient = ? AND r.id_rdv != ?
        ORDER BY r.date_rdv DESC
        LIMIT 5
    ");
    $stmt->execute([$rdv['id_patient'], $rdv_id]);
    $historique = $stmt->fetchAll();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: rendez-vous.php');
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails du rendez-vous</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Informations du rendez-vous -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Informations du rendez-vous</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table">
                                        <tr>
                                            <th>Date et heure</th>
                                            <td><?php echo date('d/m/Y H:i', strtotime($rdv['date_rdv'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Statut</th>
                                            <td>
                                                <?php
                                                $statut_classes = [
                                                    'en_attente' => 'warning',
                                                    'confirme' => 'info',
                                                    'termine' => 'success',
                                                    'annule' => 'danger'
                                                ];
                                                $statut_labels = [
                                                    'en_attente' => 'En attente',
                                                    'confirme' => 'Confirmé',
                                                    'termine' => 'Terminé',
                                                    'annule' => 'Annulé'
                                                ];
                                                ?>
                                                <span class="badge badge-<?php echo $statut_classes[$rdv['statut']]; ?>">
                                                    <?php echo $statut_labels[$rdv['statut']]; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Motif</th>
                                            <td><?php echo htmlspecialchars($rdv['motif']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Notes</th>
                                            <td>
                                                <?php if ($rdv['notes']): ?>
                                                    <?php echo nl2br(htmlspecialchars($rdv['notes'])); ?>
                                                <?php else: ?>
                                                    <em>Aucune note</em>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Informations du patient -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Informations du patient</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table">
                                        <tr>
                                            <th>Nom complet</th>
                                            <td><?php echo htmlspecialchars($rdv['patient_prenom'] . ' ' . $rdv['patient_nom']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($rdv['patient_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Téléphone</th>
                                            <td><?php echo htmlspecialchars($rdv['patient_telephone']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date de naissance</th>
                                            <td><?php echo date('d/m/Y', strtotime($rdv['patient_date_naissance'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des rendez-vous -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Historique des rendez-vous</h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($historique): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Médecin</th>
                                                        <th>Motif</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($historique as $h): ?>
                                                        <tr>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($h['date_rdv'])); ?></td>
                                                            <td><?php echo htmlspecialchars($h['medecin_prenom'] . ' ' . $h['medecin_nom']); ?></td>
                                                            <td><?php echo htmlspecialchars($h['motif']); ?></td>
                                                            <td>
                                                                <span class="badge badge-<?php echo $statut_classes[$h['statut']]; ?>">
                                                                    <?php echo $statut_labels[$h['statut']]; ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucun historique de rendez-vous</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="btn-group">
                                        <?php if ($rdv['statut'] === 'en_attente'): ?>
                                            <button type="button" class="btn btn-success" onclick="confirmerRdv(<?php echo $rdv_id; ?>)">
                                                <i class="fas fa-check"></i> Confirmer
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="annulerRdv(<?php echo $rdv_id; ?>)">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                        <?php elseif ($rdv['statut'] === 'confirme'): ?>
                                            <button type="button" class="btn btn-primary" onclick="terminerRdv(<?php echo $rdv_id; ?>)">
                                                <i class="fas fa-check-double"></i> Terminer
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="annulerRdv(<?php echo $rdv_id; ?>)">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($rdv['statut'] !== 'annule'): ?>
                                            <button type="button" class="btn btn-info" onclick="ajouterNotes(<?php echo $rdv_id; ?>)">
                                                <i class="fas fa-edit"></i> Notes
                                            </button>
                                        <?php endif; ?>

                                        <a href="rendez-vous.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Retour
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les notes -->
<div class="modal fade" id="notesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter/Modifier les notes</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="notesForm">
                    <input type="hidden" id="rdv_id" name="id_rdv">
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="sauvegarderNotes()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmerRdv(rdv_id) {
    if (confirm('Êtes-vous sûr de vouloir confirmer ce rendez-vous ?')) {
        traiterAction(rdv_id, 'confirmer');
    }
}

function terminerRdv(rdv_id) {
    if (confirm('Êtes-vous sûr de vouloir marquer ce rendez-vous comme terminé ?')) {
        traiterAction(rdv_id, 'terminer');
    }
}

function annulerRdv(rdv_id) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
        traiterAction(rdv_id, 'annuler');
    }
}

function ajouterNotes(rdv_id) {
    document.getElementById('rdv_id').value = rdv_id;
    document.getElementById('notes').value = '<?php echo addslashes($rdv['notes']); ?>';
    $('#notesModal').modal('show');
}

function sauvegarderNotes() {
    const formData = new FormData(document.getElementById('notesForm'));
    formData.append('action', 'notes');

    fetch('traiter-rdv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

function traiterAction(rdv_id, action) {
    const formData = new FormData();
    formData.append('id_rdv', rdv_id);
    formData.append('action', action);

    fetch('traiter-rdv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?> 