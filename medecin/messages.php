<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    echo '<div class="alert alert-danger">Accès non autorisé. Veuillez vous connecter en tant que médecin.</div>';
    require_once '../includes/footer.php';
    exit;
}

$error = null;
$conversations = [];
$administrateurs = [];

try {
    // Vérifier et créer la table medecins si elle n'existe pas
    $result = $pdo->query("SHOW TABLES LIKE 'medecins'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `medecins` (
                `id_medecin` int(11) NOT NULL AUTO_INCREMENT,
                `nom` varchar(50) NOT NULL,
                `prenom` varchar(50) NOT NULL,
                `email` varchar(100) NOT NULL,
                `mot_de_passe` varchar(255) NOT NULL,
                `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_medecin`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Vérifier et créer la table administrateurs si elle n'existe pas
    $result = $pdo->query("SHOW TABLES LIKE 'administrateurs'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `administrateurs` (
                `id_admin` int(11) NOT NULL AUTO_INCREMENT,
                `nom` varchar(50) NOT NULL,
                `prenom` varchar(50) NOT NULL,
                `email` varchar(100) NOT NULL,
                `mot_de_passe` varchar(255) NOT NULL,
                `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_admin`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Vérifier et créer la table conversations si elle n'existe pas
    $result = $pdo->query("SHOW TABLES LIKE 'conversations'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `conversations` (
                `id_conversation` int(11) NOT NULL AUTO_INCREMENT,
                `id_medecin` int(11) NOT NULL,
                `id_admin` int(11) NOT NULL,
                `dernier_message` text DEFAULT NULL,
                `date_dernier_message` datetime DEFAULT NULL,
                `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_conversation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Ajouter les index après la création de la table
        $pdo->exec("
            ALTER TABLE `conversations`
            ADD KEY `id_medecin` (`id_medecin`),
            ADD KEY `id_admin` (`id_admin`);
        ");
    }

    // Vérifier et créer la table messages_internes si elle n'existe pas
    $result = $pdo->query("SHOW TABLES LIKE 'messages_internes'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `messages_internes` (
                `id_message` int(11) NOT NULL AUTO_INCREMENT,
                `id_conversation` int(11) NOT NULL,
                `id_expediteur` int(11) NOT NULL,
                `message` text NOT NULL,
                `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
                `lu` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_message`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Ajouter l'index après la création de la table
        $pdo->exec("
            ALTER TABLE `messages_internes`
            ADD KEY `id_conversation` (`id_conversation`);
        ");
    }

    // Ajouter les contraintes de clés étrangères une fois que toutes les tables sont créées
    $pdo->exec("
        ALTER TABLE `conversations`
        ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`id_medecin`) REFERENCES `medecins` (`id_medecin`) ON DELETE CASCADE,
        ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `administrateurs` (`id_admin`) ON DELETE CASCADE;

        ALTER TABLE `messages_internes`
        ADD CONSTRAINT `messages_internes_ibfk_1` FOREIGN KEY (`id_conversation`) REFERENCES `conversations` (`id_conversation`) ON DELETE CASCADE;
    ");

    // Récupérer les conversations du médecin
    $stmt = $pdo->prepare("
        SELECT c.*, 
               a.nom as admin_nom, 
               a.prenom as admin_prenom,
               (SELECT COUNT(*) FROM messages_internes m WHERE m.id_conversation = c.id_conversation AND m.lu = 0 AND m.id_expediteur != ?) as messages_non_lus
        FROM conversations c
        LEFT JOIN administrateurs a ON c.id_admin = a.id_admin
        WHERE c.id_medecin = ?
        ORDER BY c.date_dernier_message DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $conversations = $stmt->fetchAll();

    // Récupérer la liste des administrateurs pour le modal
    $stmt = $pdo->query("SELECT id_admin, nom, prenom FROM administrateurs ORDER BY nom, prenom");
    $administrateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Une erreur est survenue lors de l'accès à la base de données : " . $e->getMessage();
}
?>

<div class="container-fluid py-4">
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6>Messages</h6>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouveauMessage">
                            <i class="fas fa-plus me-2"></i>Nouveau message
                        </button>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <?php if (empty($conversations)): ?>
                        <div class="alert alert-info m-4">
                            <i class="fas fa-info-circle me-2"></i>Vous n'avez pas encore de conversations.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Interlocuteur</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Dernier message</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conversations as $conversation): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div>
                                                        <i class="fas fa-user-md me-3"></i>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($conversation['admin_prenom'] . ' ' . $conversation['admin_nom']); ?></h6>
                                                        <?php if ($conversation['messages_non_lus'] > 0): ?>
                                                            <span class="badge bg-danger"><?php echo $conversation['messages_non_lus']; ?> nouveau(x) message(s)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?php echo htmlspecialchars(substr($conversation['dernier_message'], 0, 50)) . (strlen($conversation['dernier_message']) > 50 ? '...' : ''); ?></p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-secondary text-xs font-weight-bold"><?php echo date('d/m/Y H:i', strtotime($conversation['date_dernier_message'])); ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="conversation.php?id=<?php echo $conversation['id_conversation']; ?>" class="btn btn-link text-secondary mb-0">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Message -->
<div class="modal fade" id="nouveauMessage" tabindex="-1" role="dialog" aria-labelledby="nouveauMessageLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nouveauMessageLabel">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="nouveau-message.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin" class="form-label">Destinataire</label>
                        <select class="form-select" id="admin" name="id_admin" required>
                            <option value="">Sélectionnez un administrateur</option>
                            <?php foreach ($administrateurs as $admin): ?>
                                <option value="<?php echo $admin['id_admin']; ?>">
                                    <?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 