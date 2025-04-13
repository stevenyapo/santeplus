<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Vérifier si l'ID de la conversation est fourni
if (!isset($_GET['id'])) {
    header('Location: messages.php');
    exit;
}

// Récupérer les détails de la conversation
$stmt = $pdo->prepare("
    SELECT c.*, 
           a.nom as admin_nom, 
           a.prenom as admin_prenom,
           m.nom as medecin_nom,
           m.prenom as medecin_prenom
    FROM conversations c
    LEFT JOIN administrateurs a ON c.id_admin = a.id_admin
    LEFT JOIN medecins m ON c.id_medecin = m.id_medecin
    WHERE c.id_conversation = ? AND (c.id_medecin = ? OR c.id_admin = ?)
");
$stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$conversation = $stmt->fetch();

// Si la conversation n'existe pas ou n'appartient pas au médecin
if (!$conversation) {
    header('Location: messages.php');
    exit;
}

// Marquer les messages comme lus
$pdo->prepare("UPDATE messages SET lu = 1 WHERE id_conversation = ? AND id_expediteur != ?")->execute([$_GET['id'], $_SESSION['user_id']]);

// Récupérer les messages de la conversation
$stmt = $pdo->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.id_expediteur = ? THEN 'moi'
               WHEN a.id_admin IS NOT NULL THEN 'admin'
               ELSE 'medecin'
           END as type_expediteur,
           CASE 
               WHEN m.id_expediteur = ? THEN CONCAT(m2.prenom, ' ', m2.nom)
               WHEN a.id_admin IS NOT NULL THEN CONCAT(a.prenom, ' ', a.nom)
               ELSE CONCAT(m2.prenom, ' ', m2.nom)
           END as nom_expediteur
    FROM messages m
    LEFT JOIN administrateurs a ON m.id_expediteur = a.id_admin
    LEFT JOIN medecins m2 ON m.id_expediteur = m2.id_medecin
    WHERE m.id_conversation = ?
    ORDER BY m.date_envoi ASC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_GET['id']]);
$messages = $stmt->fetchAll();

// Traitement du formulaire d'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO messages (id_conversation, id_expediteur, message, date_envoi)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_POST['message']]);

        // Mettre à jour la date du dernier message
        $pdo->prepare("
            UPDATE conversations 
            SET dernier_message = ?, date_dernier_message = NOW()
            WHERE id_conversation = ?
        ")->execute([$_POST['message'], $_GET['id']]);

        header('Location: conversation.php?id=' . $_GET['id']);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue lors de l'envoi du message.";
    }
}

// Déterminer le nom de l'interlocuteur
$interlocuteur = $conversation['id_medecin'] == $_SESSION['user_id'] 
    ? $conversation['admin_prenom'] . ' ' . $conversation['admin_nom']
    : $conversation['medecin_prenom'] . ' ' . $conversation['medecin_nom'];
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6>Conversation avec <?php echo htmlspecialchars($interlocuteur); ?></h6>
                            <p class="text-sm mb-0">Dernier message : <?php echo date('d/m/Y H:i', strtotime($conversation['date_dernier_message'])); ?></p>
                        </div>
                        <a href="messages.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <!-- Messages -->
                    <div class="messages-container" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($messages as $message): ?>
                            <div class="message mb-3 <?php echo $message['type_expediteur'] === 'moi' ? 'text-end' : ''; ?>">
                                <div class="d-flex <?php echo $message['type_expediteur'] === 'moi' ? 'justify-content-end' : 'justify-content-start'; ?>">
                                    <div class="message-content <?php echo $message['type_expediteur'] === 'moi' ? 'bg-primary text-white' : 'bg-light'; ?> p-3 rounded" style="max-width: 70%;">
                                        <div class="message-header mb-2">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($message['nom_expediteur']); ?> - 
                                                <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Formulaire d'envoi de message -->
                    <form method="POST" class="mt-4">
                        <div class="row">
                            <div class="col-md-10">
                                <textarea name="message" class="form-control" rows="3" placeholder="Écrivez votre message..." required></textarea>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Faire défiler vers le bas automatiquement
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.querySelector('.messages-container');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
});
</script>

<?php require_once '../includes/footer.php'; ?> 