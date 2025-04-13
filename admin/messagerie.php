<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer l'ID de l'administrateur connecté
$id_admin = $_SESSION['user_id'];

// Traitement de l'envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'envoyer') {
        $destinataire_id = $_POST['destinataire_id'];
        $destinataire_role = $_POST['destinataire_role'];
        $sujet = $_POST['sujet'];
        $contenu = $_POST['contenu'];

        $stmt = $pdo->prepare("INSERT INTO messages_internes (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu) VALUES (?, 'admin', ?, ?, ?, ?)");
        if ($stmt->execute([$id_admin, $destinataire_id, $destinataire_role, $sujet, $contenu])) {
            $_SESSION['message_success'] = 'Message envoyé avec succès';
            header('Location: /santeplus/admin/messagerie.php');
            exit;
        } else {
            $_SESSION['message_error'] = 'Erreur lors de l\'envoi du message';
            header('Location: /santeplus/admin/messagerie.php');
            exit;
        }
    }
}

// Inclure le header après le traitement
require_once '../includes/header.php';

// Afficher les messages de succès ou d'erreur
if (isset($_SESSION['message_success'])) {
    echo "<script>Swal.fire('Succès', '" . $_SESSION['message_success'] . "', 'success');</script>";
    unset($_SESSION['message_success']);
}
if (isset($_SESSION['message_error'])) {
    echo "<script>Swal.fire('Erreur', '" . $_SESSION['message_error'] . "', 'error');</script>";
    unset($_SESSION['message_error']);
}

// Récupérer la liste des médecins
$stmt = $pdo->query("SELECT id_medecin as id, nom, prenom, 'medecin' as role FROM medecins ORDER BY nom, prenom");
$medecins = $stmt->fetchAll();

// Récupérer les messages reçus
$stmt = $pdo->prepare("
    SELECT m.*, 
        CASE 
            WHEN m.expediteur_role = 'medecin' THEN med.nom
        END as expediteur_nom,
        CASE 
            WHEN m.expediteur_role = 'medecin' THEN med.prenom
        END as expediteur_prenom,
        m.expediteur_role
    FROM messages_internes m 
    LEFT JOIN medecins med ON m.expediteur_id = med.id_medecin AND m.expediteur_role = 'medecin'
    WHERE m.destinataire_id = ? 
    AND m.destinataire_role = 'admin' 
    AND m.supprime_destinataire = FALSE 
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$id_admin]);
$messages_recus = $stmt->fetchAll();

// Marquer les messages comme lus
if (!empty($messages_recus)) {
    $stmt = $pdo->prepare("
        UPDATE messages_internes 
        SET lu = TRUE 
        WHERE destinataire_id = ? 
        AND destinataire_role = 'admin' 
        AND lu = FALSE
    ");
    $stmt->execute([$id_admin]);
}

// Récupérer les messages envoyés
$stmt = $pdo->prepare("
    SELECT m.*, 
        CASE 
            WHEN m.destinataire_role = 'medecin' THEN med.nom
        END as destinataire_nom,
        CASE 
            WHEN m.destinataire_role = 'medecin' THEN med.prenom
        END as destinataire_prenom,
        m.destinataire_role
    FROM messages_internes m 
    LEFT JOIN medecins med ON m.destinataire_id = med.id_medecin AND m.destinataire_role = 'medecin'
    WHERE m.expediteur_id = ? 
    AND m.expediteur_role = 'admin' 
    AND m.supprime_expediteur = FALSE 
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$id_admin]);
$messages_envoyes = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="row">
        <!-- Formulaire d'envoi de message -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Nouveau message</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="envoyer">
                        <div class="mb-3">
                            <label for="destinataire_role" class="form-label">Type de destinataire</label>
                            <select class="form-select" id="destinataire_role" name="destinataire_role" required>
                                <option value="">Choisir un type</option>
                                <option value="medecin">Médecin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="destinataire_id" class="form-label">Destinataire</label>
                            <select class="form-select" name="destinataire_id" id="destinataire_id" required>
                                <option value="">Choisir un destinataire</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet</label>
                            <input type="text" class="form-control" name="sujet" required>
                        </div>
                        <div class="mb-3">
                            <label for="contenu" class="form-label">Message</label>
                            <textarea class="form-control" name="contenu" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des messages -->
        <div class="col-md-8">
            <ul class="nav nav-tabs mb-3" id="messagesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#recus" type="button">
                        <span class="text-success">Reçus</span> <span class="badge bg-success"><?php echo count($messages_recus); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#envoyes" type="button">
                        <span class="text-info">Envoyés</span> <span class="badge bg-info"><?php echo count($messages_envoyes); ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Messages reçus -->
                <div class="tab-pane fade show active" id="recus">
                    <?php foreach ($messages_recus as $message): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>
                                        <?php 
                                        echo $message['expediteur_role'] === 'medecin' ? 'Dr. ' : '';
                                        echo htmlspecialchars($message['expediteur_prenom'] . ' ' . $message['expediteur_nom']); 
                                        echo ' (' . ucfirst($message['expediteur_role']) . ')';
                                        ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></small>
                                </div>
                                <?php if (!$message['lu']): ?>
                                    <span class="badge bg-warning">Nouveau</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2"><?php echo htmlspecialchars($message['sujet']); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($message['contenu'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Messages envoyés -->
                <div class="tab-pane fade" id="envoyes">
                    <?php foreach ($messages_envoyes as $message): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>
                                    À: <?php 
                                    echo $message['destinataire_role'] === 'medecin' ? 'Dr. ' : '';
                                    echo htmlspecialchars($message['destinataire_prenom'] . ' ' . $message['destinataire_nom']); 
                                    echo ' (' . ucfirst($message['destinataire_role']) . ')';
                                    ?>
                                </strong>
                                <br>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></small>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2"><?php echo htmlspecialchars($message['sujet']); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($message['contenu'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les onglets Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#messagesTab button'))
    triggerTabList.forEach(function(triggerEl) {
        new bootstrap.Tab(triggerEl)
    });

    // Données des destinataires
    const medecins = <?php echo json_encode($medecins); ?>;

    // Gérer le changement de type de destinataire
    document.getElementById('destinataire_role').addEventListener('change', function() {
        const destinataireSelect = document.getElementById('destinataire_id');
        destinataireSelect.innerHTML = '<option value="">Choisir un destinataire</option>';
        
        const destinataires = this.value === 'medecin' ? medecins : [];
        destinataires.forEach(function(destinataire) {
            const option = document.createElement('option');
            option.value = destinataire.id;
            option.textContent = (destinataire.role === 'medecin' ? 'Dr. ' : '') + 
                               destinataire.prenom + ' ' + destinataire.nom;
            destinataireSelect.appendChild(option);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 