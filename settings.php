<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /santeplus/login.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Récupérer les informations de l'utilisateur
$table = match($_SESSION['role']) {
    'admin' => 'administrateurs',
    'patient' => 'patients',
    'medecin' => 'medecins',
    'secretaire' => 'secretaire',
    default => throw new Exception('Type d\'utilisateur invalide')
};

$id_field = 'id_' . $_SESSION['role'];
$stmt = $pdo->prepare("SELECT * FROM $table WHERE $id_field = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Vérifier que le mot de passe actuel est correct
                if (!password_verify($current_password, $user['mot_de_passe'])) {
                    $error = 'Le mot de passe actuel est incorrect.';
                } 
                // Vérifier que les nouveaux mots de passe correspondent
                else if ($new_password !== $confirm_password) {
                    $error = 'Les nouveaux mots de passe ne correspondent pas.';
                } 
                // Vérifier la longueur du mot de passe
                else if (strlen($new_password) < 8) {
                    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                } 
                // Vérifier la complexité du mot de passe
                else if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                    $error = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
                } 
                else {
                    // Hasher le nouveau mot de passe
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe
                    $stmt = $pdo->prepare("UPDATE $table SET mot_de_passe = ? WHERE $id_field = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    $success = 'Votre mot de passe a été changé avec succès.';
                }
                break;
                
            case 'change_email':
                $new_email = cleanInput($_POST['new_email']);
                $password = $_POST['password'];
                
                // Vérifier que le mot de passe est correct
                if (!password_verify($password, $user['mot_de_passe'])) {
                    $error = 'Le mot de passe est incorrect.';
                } 
                // Vérifier que l'email n'est pas déjà utilisé
                else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ? AND $id_field != ?");
                    $stmt->execute([$new_email, $_SESSION['user_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Cet email est déjà utilisé par un autre compte.';
                    } else {
                        // Mettre à jour l'email
                        $stmt = $pdo->prepare("UPDATE $table SET email = ? WHERE $id_field = ?");
                        $stmt->execute([$new_email, $_SESSION['user_id']]);
                        
                        $success = 'Votre email a été changé avec succès.';
                    }
                }
                break;
        }
    }
}

// Inclure le header
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cog me-2"></i>Paramètres
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- Onglets -->
                    <style>
                        .nav-tabs .nav-link {
                            color: #333 !important;
                            background-color: #f8f9fa !important;
                            border-color: #dee2e6 #dee2e6 #fff !important;
                        }
                        .nav-tabs .nav-link.active {
                            color: #0d6efd !important;
                            background-color: #fff !important;
                            border-color: #dee2e6 #dee2e6 #fff !important;
                        }
                        .nav-tabs .nav-link:hover {
                            color: #0d6efd !important;
                            border-color: #dee2e6 #dee2e6 #dee2e6 !important;
                        }
                    </style>
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                                <i class="fas fa-key me-2"></i>Mot de passe
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope me-2"></i>Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- Onglet Mot de passe -->
                        <div class="tab-pane fade show active" id="password" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">
                                        Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                        
                        <!-- Onglet Email -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_email">
                                
                                <div class="mb-3">
                                    <label class="form-label">Email actuel</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_email" class="form-label">Nouvel email</label>
                                    <input type="email" class="form-control" id="new_email" name="new_email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                        
                        <!-- Onglet Notifications -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                        <label class="form-check-label" for="email_notifications">
                                            Recevoir des notifications par email
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="app_notifications" name="app_notifications" checked>
                                        <label class="form-check-label" for="app_notifications">
                                            Recevoir des notifications dans l'application
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les préférences
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-primary" onclick="window.location.href='/santeplus/profile.php'">
                            <i class="fas fa-user me-2"></i>Profil
                        </button>
                        <button class="btn btn-primary" onclick="window.location.href='/santeplus/<?php echo $_SESSION['role']; ?>/dashboard.php'">
                            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le footer
require_once 'includes/footer.php';
?> 