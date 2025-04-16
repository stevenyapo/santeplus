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
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --bg-color: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-color: #ffffff;
            --input-bg: #3d3d3d;
            --input-border: #4d4d4d;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
            animation: fadeInDown 1s ease;
        }
        
        .login-logo i {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        .login-logo h2 {
            font-weight: 600;
            margin: 0;
            background: linear-gradient(45deg, var(--primary-color), #8BC34A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-control {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-color);
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--primary-color);
            color: var(--text-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
            transform: translateY(-2px);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .btn-primary:active::after {
            animation: ripple 1s ease-out;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .input-group .form-control {
            padding-left: 45px;
        }
        
        .input-group .form-control:focus + i {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.2);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(100, 100);
                opacity: 0;
            }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: var(--primary-color);
            border-radius: 50%;
            opacity: 0.1;
            animation: particle-animation 15s infinite;
        }
        
        @keyframes particle-animation {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            50% {
                opacity: 0.1;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <?php for($i = 0; $i < 20; $i++): ?>
            <div class="particle" style="
                width: <?php echo rand(5, 20); ?>px;
                height: <?php echo rand(5, 20); ?>px;
                left: <?php echo rand(0, 100); ?>%;
                top: <?php echo rand(0, 100); ?>%;
                animation-delay: <?php echo rand(0, 10); ?>s;
                animation-duration: <?php echo rand(10, 20); ?>s;
            "></div>
        <?php endfor; ?>
    </div>
    
    <div class="login-container animate__animated animate__fadeIn">
        <div class="login-logo">
            <i class="fas fa-heartbeat floating"></i>
            <h2>SantéPlus</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__shakeX">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="animate__animated animate__fadeInUp">
            <div class="input-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <i class="fas fa-envelope"></i>
            </div>
            
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <i class="fas fa-lock"></i>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>
                Se connecter
            </button>
        </form>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation des particules
        document.addEventListener('DOMContentLoaded', function() {
            const particles = document.querySelectorAll('.particle');
            particles.forEach(particle => {
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
            });
        });
        
        // Animation des champs de formulaire
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html> 