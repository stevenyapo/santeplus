<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer les filtres
$statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
$sujet = isset($_GET['sujet']) ? cleanInput($_GET['sujet']) : '';
$date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';

// Construire la requête SQL
$sql = "SELECT * FROM messages_contact WHERE 1=1";
$params = [];

if ($statut) {
    $sql .= " AND statut = ?";
    $params[] = $statut;
}

if ($sujet) {
    $sql .= " AND sujet = ?";
    $params[] = $sujet;
}

if ($date_debut) {
    $sql .= " AND date_creation >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if ($date_fin) {
    $sql .= " AND date_creation <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$sql .= " ORDER BY date_creation DESC";

// Exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Récupérer les statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn(),
    'nouveaux' => $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE statut = 'nouveau'")->fetchColumn(),
    'lus' => $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE statut = 'lu'")->fetchColumn(),
    'repondus' => $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE statut = 'repondu'")->fetchColumn()
];
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Messages de contact</h6>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filtresModal">
                            <i class="fas fa-filter me-2"></i>Filtres
                        </button>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nom</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Email</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Sujet</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm"><?php echo date('d/m/Y H:i', strtotime($message['date_creation'])); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-sm font-weight-bold mb-0"><?php echo htmlspecialchars($message['nom']); ?></p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-secondary"><?php echo htmlspecialchars($message['email']); ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold"><?php echo htmlspecialchars($message['sujet']); ?></span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php
                                        $badge_class = [
                                            'nouveau' => 'bg-gradient-danger',
                                            'lu' => 'bg-gradient-warning',
                                            'repondu' => 'bg-gradient-success'
                                        ];
                                        $badge_text = [
                                            'nouveau' => 'Nouveau',
                                            'lu' => 'Lu',
                                            'repondu' => 'Répondu'
                                        ];
                                        ?>
                                        <span class="badge badge-sm <?php echo $badge_class[$message['statut']]; ?>">
                                            <?php echo $badge_text[$message['statut']]; ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <button type="button" class="btn btn-link text-secondary mb-0" onclick="voirMessage(<?php echo $message['id_message']; ?>)">
                                            <i class="fas fa-eye text-xs"></i>
                                        </button>
                                        <button type="button" class="btn btn-link text-secondary mb-0" onclick="repondreMessage(<?php echo $message['id_message']; ?>)">
                                            <i class="fas fa-reply text-xs"></i>
                                        </button>
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

<!-- Modal Filtres -->
<div class="modal fade" id="filtresModal" tabindex="-1" aria-labelledby="filtresModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filtresModalLabel">Filtres</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="">Tous</option>
                            <option value="nouveau" <?php echo $statut === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                            <option value="lu" <?php echo $statut === 'lu' ? 'selected' : ''; ?>>Lu</option>
                            <option value="repondu" <?php echo $statut === 'repondu' ? 'selected' : ''; ?>>Répondu</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet</label>
                        <select class="form-select" id="sujet" name="sujet">
                            <option value="">Tous</option>
                            <option value="rendez-vous" <?php echo $sujet === 'rendez-vous' ? 'selected' : ''; ?>>Rendez-vous</option>
                            <option value="urgences" <?php echo $sujet === 'urgences' ? 'selected' : ''; ?>>Urgences</option>
                            <option value="documents" <?php echo $sujet === 'documents' ? 'selected' : ''; ?>>Documents médicaux</option>
                            <option value="teleconsultation" <?php echo $sujet === 'teleconsultation' ? 'selected' : ''; ?>>Téléconsultation</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Voir Message -->
<div class="modal fade" id="voirMessageModal" tabindex="-1" aria-labelledby="voirMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voirMessageModalLabel">Détails du message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="messageDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Répondre -->
<div class="modal fade" id="repondreModal" tabindex="-1" aria-labelledby="repondreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="repondreModalLabel">Répondre au message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="repondreForm" method="POST" action="repondre-message.php">
                <div class="modal-body">
                    <input type="hidden" id="message_id" name="message_id">
                    <div id="messageInfo"></div>
                    
                    <div class="mb-3">
                        <label for="reponse" class="form-label">Votre réponse</label>
                        <textarea class="form-control" id="reponse" name="reponse" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function voirMessage(id) {
    fetch(`get-message-details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.message;
                document.getElementById('messageDetails').innerHTML = `
                    <div class="mb-3">
                        <strong>De :</strong> ${message.nom}<br>
                        <strong>Email :</strong> ${message.email}<br>
                        <strong>Sujet :</strong> ${message.sujet}<br>
                        <strong>Date :</strong> ${message.date_creation}
                    </div>
                    <div class="border rounded p-3">
                        ${message.message}
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('voirMessageModal')).show();
            }
        });
}

function repondreMessage(id) {
    fetch(`get-message-details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.message;
                document.getElementById('message_id').value = id;
                document.getElementById('messageInfo').innerHTML = `
                    <div class="mb-3">
                        <strong>De :</strong> ${message.nom}<br>
                        <strong>Email :</strong> ${message.email}<br>
                        <strong>Sujet :</strong> ${message.sujet}<br>
                        <strong>Message :</strong><br>
                        <div class="border rounded p-3 mb-3">
                            ${message.message}
                        </div>
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('repondreModal')).show();
            }
        });
}
</script>

<?php require_once '../includes/footer.php'; ?> 