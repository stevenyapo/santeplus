<?php
// Démarrer la mise en tampon de sortie
ob_start();

require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    header('Location: /santeplus/login.php');
    exit();
}

// Traitement du formulaire de validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $id_rapport = $_POST['id_rapport'];
        
        if ($_POST['action'] === 'valider') {
            // Mettre à jour le statut du rapport
            $stmt = $pdo->prepare("UPDATE rapports_hemodialyse SET statut = 'validé' WHERE id_rapport = ?");
            $stmt->execute([$id_rapport]);
            
            // Récupérer les informations du rapport
            $stmt = $pdo->prepare("
                SELECT r.*, m.id_medecin, m.email as medecin_email, m.nom as medecin_nom, m.prenom as medecin_prenom
                FROM rapports_hemodialyse r
                JOIN medecins m ON r.id_medecin = m.id_medecin
                WHERE r.id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Envoyer une notification au médecin
            $stmt = $pdo->prepare("
                INSERT INTO notifications (message, type_notification, lien, type_destinataire, destinataire_id)
                VALUES (?, 'success', ?, 'medecin', ?)
            ");
            $stmt->execute([
                "Votre rapport du " . date('d/m/Y', strtotime($rapport['date_rapport'])) . " a été validé par l'administrateur.",
                "medecin/rapports_hemodialyse.php",
                $rapport['id_medecin']
            ]);
            
            $_SESSION['success_message'] = "Le rapport a été validé avec succès.";
        } elseif ($_POST['action'] === 'rejeter') {
            $commentaire = $_POST['commentaire'] ?? '';
            
            // Mettre à jour le statut du rapport et ajouter le commentaire
            $stmt = $pdo->prepare("
                UPDATE rapports_hemodialyse 
                SET statut = 'rejeté', commentaire_admin = ?
                WHERE id_rapport = ?
            ");
            $stmt->execute([$commentaire, $id_rapport]);
            
            // Récupérer les informations du rapport
            $stmt = $pdo->prepare("
                SELECT r.*, m.id_medecin, m.email as medecin_email, m.nom as medecin_nom, m.prenom as medecin_prenom
                FROM rapports_hemodialyse r
                JOIN medecins m ON r.id_medecin = m.id_medecin
                WHERE r.id_rapport = ?
            ");
            $stmt->execute([$id_rapport]);
            $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Envoyer une notification au médecin
            $stmt = $pdo->prepare("
                INSERT INTO notifications (message, type_notification, lien, type_destinataire, destinataire_id)
                VALUES (?, 'danger', ?, 'medecin', ?)
            ");
            $stmt->execute([
                "Votre rapport du " . date('d/m/Y', strtotime($rapport['date_rapport'])) . " a été rejeté par l'administrateur. Raison : " . $commentaire,
                "medecin/rapports_hemodialyse.php",
                $rapport['id_medecin']
            ]);
            
            $_SESSION['success_message'] = "Le rapport a été rejeté avec succès.";
        }
        
        // Nettoyer le tampon de sortie avant la redirection
        ob_end_clean();
        header('Location: rapports_hemodialyse.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors du traitement : " . $e->getMessage();
    }
}

// Modifier la requête SQL pour prendre en compte les filtres
$periode = $_GET['periode'] ?? 'mois';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01', strtotime('-11 months'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-t');

$format_date = match($periode) {
    'mois' => '%Y-%m',
    'trimestre' => '%Y-Q%q',
    'semestre' => '%Y-S%q',
    'annee' => '%Y',
    default => '%Y-%m'
};

$stmt = $pdo->prepare("
    SELECT r.*, m.nom as medecin_nom, m.prenom as medecin_prenom
    FROM rapports_hemodialyse r
    JOIN medecins m ON r.id_medecin = m.id_medecin
    WHERE r.date_rapport BETWEEN ? AND ?
    ORDER BY r.date_creation DESC
");
$stmt->execute([$date_debut, $date_fin]);
$rapports = $stmt->fetchAll();
?>

<!-- Styles DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<style>
    .dataTables_wrapper .dataTables_filter {
        float: none;
        text-align: left;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_length {
        float: none;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper .dataTables_info {
        padding-top: 1rem;
    }
    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
    }
    .dataTables_wrapper .form-select {
        width: 100%;
        margin-bottom: 1rem;
    }
    .dataTables_wrapper th {
        position: relative;
    }
    .dataTables_wrapper th select {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .dataTables_wrapper th:hover {
        background-color: #f8f9fa;
    }
</style>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
// Fonction pour afficher les détails du rapport
function viewReport(reportId) {
    fetch(`get_report_details.php?id=${reportId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Une erreur est survenue lors du chargement des détails du rapport.');
            }
            const details = document.getElementById('reportDetails');
            details.innerHTML = data.html;
            const modal = new bootstrap.Modal(document.getElementById('viewReportModal'), {
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            modal.show();
        })
        .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: error.message || 'Une erreur est survenue lors du chargement des détails du rapport.'
            });
        });
}

// Fonction pour valider un rapport
function validateReport(reportId) {
    document.getElementById('validateReportId').value = reportId;
    const modal = new bootstrap.Modal(document.getElementById('validateReportModal'), {
        backdrop: 'static',
        keyboard: true,
        focus: true
    });
    modal.show();
}

// Fonction pour rejeter un rapport
function rejectReport(reportId) {
    document.getElementById('rejectReportId').value = reportId;
    const modal = new bootstrap.Modal(document.getElementById('rejectReportModal'), {
        backdrop: 'static',
        keyboard: true,
        focus: true
    });
    modal.show();
}

function downloadPDF(id) {
    window.location.href = `generate_pdf.php?id=${id}`;
}

function deleteReport(id) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action est irréversible !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_rapport.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_rapport=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Supprimé !',
                        'Le rapport a été supprimé avec succès.',
                        'success'
                    ).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire(
                        'Erreur !',
                        data.message || 'Une erreur est survenue lors de la suppression.',
                        'error'
                    );
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                Swal.fire(
                    'Erreur !',
                    'Une erreur est survenue lors de la suppression.',
                    'error'
                );
            });
        }
    });
}

// Initialiser DataTables
document.addEventListener('DOMContentLoaded', function() {
    // Supprimer le script jQuery du footer s'il existe
    const footerScripts = document.querySelectorAll('script[src*="jquery"]');
    footerScripts.forEach(script => {
        if (script.src.includes('3.6.0.min.js')) {
            script.remove();
        }
    });

    // Initialiser DataTables
    $('#rapportsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json',
            search: "Rechercher :",
            searchPlaceholder: "Rechercher dans tous les champs...",
            lengthMenu: "Afficher _MENU_ entrées par page",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
            infoEmpty: "Aucune entrée à afficher",
            infoFiltered: "(filtré sur _MAX_ entrées au total)",
            paginate: {
                first: "Premier",
                last: "Dernier",
                next: "Suivant",
                previous: "Précédent"
            }
        },
        order: [[5, 'desc']], // Trier par date de soumission par défaut
        pageLength: 10, // Nombre d'entrées par page
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]], // Options de pagination
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            // Ajouter des filtres personnalisés
            this.api().columns([1, 2, 4]).every(function() {
                var column = this;
                var select = $('<select class="form-select form-select-sm mb-2"><option value="">Tous</option></select>')
                    .appendTo($(column.header()))
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                column.data().unique().sort().each(function(d) {
                    if (d) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    }
                });
            });
        }
    });
});
</script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Gestion des rapports d'hémodialyse</h5>
                    <div class="d-flex gap-2">
                        <a href="graphiques_hemodialyse.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-chart-bar"></i> Voir les graphiques
                        </a>
                        <a href="graphique_global.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-chart-bar"></i> Voir le graphique global
                        </a>
                    </div>
                    
                </div>
                <div class="card-body">
                    <!-- Formulaire de filtrage -->
                    <form method="get" class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label for="periode" class="form-label">Période</label>
                            <select class="form-select" id="periode" name="periode">
                                <option value="mois" <?php echo ($_GET['periode'] ?? 'mois') === 'mois' ? 'selected' : ''; ?>>Mensuel</option>
                                <option value="trimestre" <?php echo ($_GET['periode'] ?? '') === 'trimestre' ? 'selected' : ''; ?>>Trimestriel</option>
                                <option value="semestre" <?php echo ($_GET['periode'] ?? '') === 'semestre' ? 'selected' : ''; ?>>Semestriel</option>
                                <option value="annee" <?php echo ($_GET['periode'] ?? '') === 'annee' ? 'selected' : ''; ?>>Annuel</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $_GET['date_debut'] ?? date('Y-m-01', strtotime('-11 months')); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $_GET['date_fin'] ?? date('Y-m-t'); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </div>
                    </form>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover" id="rapportsTable">
                            <thead>
                                <tr>
                                    <th>Date rapport</th>
                                    <th>Antenne</th>
                                    <th>Médecin</th>
                                    <th>Total patients</th>
                                    <th>Statut</th>
                                    <th>Date soumission</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapports as $rapport): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($rapport['date_rapport'])); ?></td>
                                        <td><?php echo htmlspecialchars($rapport['antenne']); ?></td>
                                        <td><?php echo htmlspecialchars($rapport['medecin_prenom'] . ' ' . $rapport['medecin_nom']); ?></td>
                                        <td><?php echo $rapport['total_patients_dialyses']; ?></td>
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
                                        <td><?php echo date('d/m/Y H:i', strtotime($rapport['date_creation'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="viewReport(<?php echo $rapport['id_rapport']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" onclick="validateReport(<?php echo $rapport['id_rapport']; ?>)" <?php echo $rapport['statut'] !== 'en_attente' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="rejectReport(<?php echo $rapport['id_rapport']; ?>)" <?php echo $rapport['statut'] !== 'en_attente' ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="downloadPDF(<?php echo $rapport['id_rapport']; ?>)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteReport(<?php echo $rapport['id_rapport']; ?>)">
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
    </div>
</div>

<!-- Modal pour afficher les détails du rapport -->
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewReportModalLabel">Détails du rapport</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="reportDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour valider le rapport -->
<div class="modal fade" id="validateReportModal" tabindex="-1" aria-labelledby="validateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validateReportModalLabel">Valider le rapport</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir valider ce rapport ?</p>
                    <input type="hidden" name="id_rapport" id="validateReportId">
                    <input type="hidden" name="action" value="valider">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour rejeter le rapport -->
<div class="modal fade" id="rejectReportModal" tabindex="-1" aria-labelledby="rejectReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectReportModalLabel">Rejeter le rapport</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <p>Veuillez indiquer la raison du rejet :</p>
                    <input type="hidden" name="id_rapport" id="rejectReportId">
                    <input type="hidden" name="action" value="rejeter">
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 