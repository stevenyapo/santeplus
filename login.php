<?php
include 'includes/db.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Logs de débogage
error_log("=== Debug Session Login ===");
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

require_once 'config/database.php';
require_once 'includes/functions.php';

// Si l'utilisateur est déjà connecté, le rediriger vers la page appropriée
if (isset($_SESSION['user_id']) && !isset($_GET['timeout'])) {
    $userType = $_SESSION['user_type'];
    
    if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true) {
        header('Location: change_password.php');
        exit();
    }
    
    // Rediriger vers la page appropriée selon le type d'utilisateur
    switch ($userType) {
        case 'patient':
            header('Location: patient/dashboard.php');
            break;
        case 'medecin':
            header('Location: medecin/dashboard.php');
            break;
        case 'secretaire':
            header('Location: secretaire/dashboard.php');
            break;
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        default:
            // Si le type d'utilisateur n'est pas reconnu, déconnecter l'utilisateur
            session_unset();
            session_destroy();
            break;
    }
    exit();
}

$error = '';
$success = '';

// Vérifier si la déconnexion est due à un timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Votre session a expiré en raison d\'une inactivité de 10 minutes. Veuillez vous reconnecter.';
} 
// Vérifier s'il y a un message d'erreur de session
else if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    error_log("Tentative de connexion avec l'email: " . $email);
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
        error_log("Champs vides détectés");
    } else {
        try {
            // Vérifier dans la table administrateurs
            $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Administrateur trouvé: " . print_r($user, true));
                if (password_verify($password, $user['mot_de_passe'])) {
                    // Connexion réussie pour un administrateur
                    $_SESSION['user_id'] = $user['id_admin'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['last_activity'] = time();
                    
                    error_log("Connexion réussie pour l'administrateur: " . $email);
                    error_log("Session créée: " . print_r($_SESSION, true));
                    error_log("Redirection vers: /santeplus/admin/dashboard.php");
                    header('Location: /santeplus/admin/dashboard.php');
                    exit();
                } else {
                    error_log("Mot de passe incorrect pour l'administrateur");
                }
            }
            
            // Vérifier dans la table medecins
            $stmt = $pdo->prepare("SELECT * FROM medecins WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Médecin trouvé: " . print_r($user, true));
                if (password_verify($password, $user['mot_de_passe'])) {
                    // Connexion réussie pour un médecin
                    $_SESSION['user_id'] = $user['id_medecin'];
                    $_SESSION['role'] = 'medecin';
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['last_activity'] = time();
                    
                    error_log("Connexion réussie pour le médecin: " . $email);
                    error_log("Session créée: " . print_r($_SESSION, true));
                    error_log("Redirection vers: /santeplus/medecin/dashboard.php");
                    header('Location: /santeplus/medecin/dashboard.php');
                    exit();
                } else {
                    error_log("Mot de passe incorrect pour le médecin");
                }
            }
            
            // Vérifier dans la table utilisateurs
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Utilisateur trouvé: " . print_r($user, true));
                if (password_verify($password, $user['mot_de_passe'])) {
                    // Connexion réussie pour un utilisateur
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['last_activity'] = time();
                    
                    error_log("Connexion réussie pour l'utilisateur: " . $email);
                    error_log("Session créée: " . print_r($_SESSION, true));
                    error_log("Redirection vers: /santeplus/" . $user['role'] . "/dashboard.php");
                    header('Location: /santeplus/' . $user['role'] . '/dashboard.php');
                    exit();
                } else {
                    error_log("Mot de passe incorrect pour l'utilisateur");
                }
            }
            
            // Si on arrive ici, c'est que l'email n'a pas été trouvé ou que le mot de passe est incorrect
            $error = 'Email ou mot de passe incorrect.';
            error_log("Échec de la connexion pour: " . $email);
            
        } catch (PDOException $e) {
            error_log("Erreur PDO: " . $e->getMessage());
            $error = 'Une erreur est survenue lors de la connexion.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SantéPlus</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/santeplus/style.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }
        
        .login-container {
            background-color: #2d2d2d;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: #4CAF50;
        }
        
        .form-control {
            background-color: #3d3d3d;
            border: 1px solid #4d4d4d;
            color: #ffffff;
        }
        
        .form-control:focus {
            background-color: #3d3d3d;
            border-color: #4CAF50;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .btn-primary {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
            border-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-heartbeat"></i>
            <h2 class="mt-3">SantéPlus</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 