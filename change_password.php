<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /santeplus/login.php');
    exit();
}

// Vérifier si l'utilisateur doit changer son mot de passe
if (!isset($_SESSION['force_password_change']) || $_SESSION['force_password_change'] !== true) {
    // Rediriger vers le tableau de bord approprié
    $dashboard = match($_SESSION['role']) {
        'admin' => '/santeplus/admin/dashboard.php',
        'patient' => '/santeplus/patient/dashboard.php',
        'medecin' => '/santeplus/medecin/dashboard.php',
        'secretaire' => '/santeplus/secretaire/dashboard.php',
        default => '/santeplus/'
    };
    header('Location: ' . $dashboard);
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vérifier que les mots de passe correspondent
    if ($new_password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
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
        
        // Mettre à jour le mot de passe dans la base de données
        $table = match($_SESSION['role']) {
            'admin' => 'administrateurs',
            'patient' => 'patients',
            'medecin' => 'medecins',
            'secretaire' => 'secretaire',
            default => throw new Exception('Type d\'utilisateur invalide')
        };
        
        $id_field = 'id_' . $_SESSION['role'];
        
        $stmt = $pdo->prepare("UPDATE $table SET mot_de_passe = ?, force_password_change = 0 WHERE $id_field = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        // Supprimer le flag de changement de mot de passe forcé
        unset($_SESSION['force_password_change']);
        
        $success = 'Votre mot de passe a été changé avec succès.';
        
        // Rediriger vers le tableau de bord après 2 secondes
        header('Refresh: 2; URL=' . match($_SESSION['role']) {
            'admin' => '/santeplus/admin/dashboard.php',
            'patient' => '/santeplus/patient/dashboard.php',
            'medecin' => '/santeplus/medecin/dashboard.php',
            'secretaire' => '/santeplus/secretaire/dashboard.php',
            default => '/santeplus/'
        });
    }
}

// Inclure le header
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Changement de mot de passe obligatoire</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Votre mot de passe a été réinitialisé par un administrateur. Vous devez le changer maintenant.
                        </div>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">
                                    Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le footer
require_once 'includes/footer.php';
?> 