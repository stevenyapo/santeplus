<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Récupérer l'ID du médecin connecté
$id_medecin = $_SESSION['user_id'];

// Traitement de l'envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'envoyer') {
        $destinataire_id = $_POST['destinataire_id'];
        $destinataire_role = $_POST['destinataire_role'];
        $sujet = $_POST['sujet'];
        $contenu = $_POST['contenu'];

        $stmt = $pdo->prepare("INSERT INTO messages_internes (expediteur_id, expediteur_role, destinataire_id, destinataire_role, sujet, contenu) VALUES (?, 'medecin', ?, ?, ?, ?)");
        if ($stmt->execute([$id_medecin, $destinataire_role, $destinataire_id, $destinataire_role, $sujet, $contenu])) {
            echo "<script>Swal.fire('Succès', 'Message envoyé avec succès', 'success');</script>";
        } else {
            echo "<script>Swal.fire('Erreur', 'Erreur lors de l\'envoi du message', 'error');</script>";
        }
    }
}

// Récupérer la liste des secrétaires
$stmt = $pdo->query("SELECT id_secretaire as id, nom, prenom, 'secretaire' as role FROM secretaire ORDER BY nom, prenom");
$secretaires = $stmt->fetchAll();

// Récupérer la liste des administrateurs
$stmt = $pdo->query("SELECT id_admin as id, nom, prenom, 'admin' as role FROM administrateurs ORDER BY nom, prenom");
$administrateurs = $stmt->fetchAll();

// Récupérer les messages reçus
$stmt = $pdo->prepare("
    SELECT m.*, s.nom as expediteur_nom, s.prenom as expediteur_prenom 
    FROM messages_internes m 
    JOIN secretaire s ON m.expediteur_id = s.id_secretaire 
    WHERE m.destinataire_id = ? 
    AND m.destinataire_role = 'medecin' 
    AND m.supprime_destinataire = FALSE 
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$id_medecin]);
$messages_recus = $stmt->fetchAll();

// Récupérer les messages envoyés
$stmt = $pdo->prepare("
    SELECT m.*, s.nom as destinataire_nom, s.prenom as destinataire_prenom 
    FROM messages_internes m 
    JOIN secretaire s ON m.destinataire_id = s.id_secretaire 
    WHERE m.expediteur_id = ? 
    AND m.expediteur_role = 'medecin' 
    AND m.supprime_expediteur = FALSE 
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$id_medecin]);
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
                                <option value="secretaire">Secrétaire</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="destinataire_id" class="form-label">Destinataire</label>
                            <select class="form-select" id="destinataire_id" name="destinataire_id" required>
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
                        Reçus <span class="badge bg-primary"><?php echo count($messages_recus); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#envoyes" type="button">
                        Envoyés <span class="badge bg-secondary"><?php echo count($messages_envoyes); ?></span>
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
                                    <strong><?php echo htmlspecialchars($message['expediteur_prenom'] . ' ' . $message['expediteur_nom']); ?></strong>
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
                                <strong>À: <?php echo htmlspecialchars($message['destinataire_prenom'] . ' ' . $message['destinataire_nom']); ?></strong>
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
    const secretaires = <?php echo json_encode($secretaires); ?>;
    const administrateurs = <?php echo json_encode($administrateurs); ?>;

    // Gérer le changement de type de destinataire
    document.getElementById('destinataire_role').addEventListener('change', function() {
        const destinataireSelect = document.getElementById('destinataire_id');
        destinataireSelect.innerHTML = '<option value="">Choisir un destinataire</option>';
        
        let destinataires;
        if (this.value === 'secretaire') {
            destinataires = secretaires;
        } else if (this.value === 'admin') {
            destinataires = administrateurs;
        }

        if (destinataires) {
            destinataires.forEach(function(destinataire) {
                const option = document.createElement('option');
                option.value = destinataire.id;
                option.textContent = destinataire.prenom + ' ' + destinataire.nom;
                destinataireSelect.appendChild(option);
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 