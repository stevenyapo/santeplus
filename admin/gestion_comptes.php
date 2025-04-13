<?php
session_start();

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Logs de débogage
error_log("=== Debug Session ===");
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    error_log("Session non initialisée - user_id ou role manquant");
    $_SESSION['error_message'] = "Veuillez vous connecter";
    header('Location: /santeplus/login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    error_log("Rôle incorrect - Rôle actuel: " . $_SESSION['role']);
    $_SESSION['error_message'] = "Accès non autorisé - Veuillez vous connecter en tant qu'administrateur";
    header('Location: /santeplus/login.php');
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// Logs de débogage
error_log("=== Début de la requête ===");
error_log("Méthode de requête : " . $_SERVER['REQUEST_METHOD']);
error_log("POST data : " . print_r($_POST, true));
error_log("GET data : " . print_r($_GET, true));

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Méthode POST détectée");
    error_log("POST data: " . print_r($_POST, true));
    
    $action = cleanInput($_POST['action']);
    error_log("Action: " . $action);
    
    if ($action === 'add') {
        $user_type = cleanInput($_POST['user_type']);
        error_log("Type d'utilisateur: " . $user_type);
    } else {
        $user_id = cleanInput($_POST['user_id']);
        $user_type = cleanInput($_POST['user_type']);
    }

    try {
        switch ($action) {
            case 'delete':
                // Supprimer le compte
                $table = match($user_type) {
                    'patient' => 'patients',
                    'medecin' => 'medecins',
                    'secretaire' => 'secretaire',
                    default => throw new Exception('Type d\'utilisateur invalide')
                };
                $stmt = $pdo->prepare("DELETE FROM $table WHERE id_" . $user_type . " = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success_message'] = "Le compte a été supprimé avec succès.";
                break;

            case 'edit':
                // Modifier les informations du compte
                $table = match($user_type) {
                    'patient' => 'patients',
                    'medecin' => 'medecins',
                    'secretaire' => 'secretaire',
                    default => throw new Exception('Type d\'utilisateur invalide')
                };

                $nom = cleanInput($_POST['nom']);
                $prenom = cleanInput($_POST['prenom']);
                $email = cleanInput($_POST['email']);
                $telephone = cleanInput($_POST['telephone']);
                $adresse = cleanInput($_POST['adresse']);

                // Vérifier si l'email existe déjà pour un autre utilisateur
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ? AND id_" . $user_type . " != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cet email est déjà utilisé par un autre utilisateur.');
                }

                if ($user_type === 'medecin') {
                    $specialite = cleanInput($_POST['specialite']);
                    $antenne = cleanInput($_POST['antenne']);
                    $sql = "UPDATE $table SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, specialite = ?, antenne = ? WHERE id_medecin = ?";
                    $params = [$nom, $prenom, $email, $telephone, $adresse, $specialite, $antenne, $user_id];
                } else if ($user_type === 'patient') {
                    $date_naissance = cleanInput($_POST['date_naissance']);
                    $sexe = cleanInput($_POST['sexe']);
                    $groupe_sanguin = cleanInput($_POST['groupe_sanguin']);
                    $allergies = cleanInput($_POST['allergies']);
                    $maladies_chroniques = cleanInput($_POST['maladies_chroniques']);
                    
                    $sql = "UPDATE $table SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, date_naissance = ?, sexe = ?, groupe_sanguin = ?, allergies = ?, maladies_chroniques = ? WHERE id_patient = ?";
                    $params = [$nom, $prenom, $email, $telephone, $adresse, $date_naissance, $sexe, $groupe_sanguin, $allergies, $maladies_chroniques, $user_id];
                } else {
                    $sql = "UPDATE $table SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id_secretaire = ?";
                    $params = [$nom, $prenom, $email, $telephone, $user_id];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = "Les informations du compte ont été mises à jour avec succès.";
                break;

            case 'restore':
                // Restaurer le mot de passe
                $new_password = bin2hex(random_bytes(8)); // Génère un mot de passe aléatoire
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $table = match($user_type) {
                    'patient' => 'patients',
                    'medecin' => 'medecins',
                    'secretaire' => 'secretaire',
                    default => throw new Exception('Type d\'utilisateur invalide')
                };
                $stmt = $pdo->prepare("UPDATE $table SET mot_de_passe = ?, force_password_change = 1 WHERE id_" . $user_type . " = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                // Redirection avec le mot de passe dans l'URL
                header('Location: ' . $_SERVER['PHP_SELF'] . '?password_reset=1&new_password=' . urlencode($new_password));
                exit();
                break;

            case 'toggle_status':
                // Basculer le statut du compte
                $table = match($user_type) {
                    'patient' => 'patients',
                    'medecin' => 'medecins',
                    'secretaire' => 'secretaire',
                    default => throw new Exception('Type d\'utilisateur invalide')
                };
                $stmt = $pdo->prepare("UPDATE $table SET statut = CASE WHEN statut = 'actif' THEN 'inactif' ELSE 'actif' END WHERE id_" . $user_type . " = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success_message'] = "Le statut du compte a été modifié avec succès.";
                break;

            case 'add':
                try {
                    // Débogage
                    error_log("Début de l'ajout d'un utilisateur");
                    error_log("Type d'utilisateur : " . $user_type);
                    
                    // Ajouter un nouveau compte
                    $nom = cleanInput($_POST['nom']);
                    $prenom = cleanInput($_POST['prenom']);
                    $email = cleanInput($_POST['email']);
                    $telephone = cleanInput($_POST['telephone']);
                    
                    // Récupérer l'adresse seulement si ce n'est pas un secrétaire
                    $adresse = ($user_type !== 'secretaire') ? cleanInput($_POST['adresse']) : '';
                    
                    error_log("Données reçues : nom=$nom, prenom=$prenom, email=$email");
                    
                    // Validation de base
                    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
                        throw new Exception('Les champs nom, prénom, email et téléphone sont obligatoires.');
                    }

                    // Validation spécifique selon le type d'utilisateur
                    if ($user_type !== 'secretaire' && empty($adresse)) {
                        throw new Exception('L\'adresse est obligatoire pour les médecins et les patients.');
                    }

                    // Vérifier si l'email existe déjà
                    $table = match($user_type) {
                        'patient' => 'patients',
                        'medecin' => 'medecins',
                        'secretaire' => 'secretaire',
                        default => throw new Exception('Type d\'utilisateur invalide')
                    };

                    error_log("Table sélectionnée : $table");

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Cet email est déjà utilisé.');
                    }
                    
                    // Générer un mot de passe aléatoire
                    $new_password = bin2hex(random_bytes(8));
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Préparer la requête en fonction du type d'utilisateur
                    if ($user_type === 'medecin') {
                        $specialite = cleanInput($_POST['specialite']);
                        $antenne = cleanInput($_POST['antenne']);
                        if (empty($specialite)) {
                            throw new Exception('La spécialité est obligatoire pour un médecin.');
                        }
                        if (empty($antenne)) {
                            throw new Exception('L\'antenne est obligatoire pour un médecin.');
                        }
                        $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, specialite, antenne, telephone, adresse, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'actif')";
                        $params = [$nom, $prenom, $email, $hashed_password, $specialite, $antenne, $telephone, $adresse];
                    } else if ($user_type === 'patient') {
                        error_log("Traitement d'un nouveau patient");
                        $date_naissance = cleanInput($_POST['date_naissance']);
                        $sexe = cleanInput($_POST['sexe']);
                        $groupe_sanguin = cleanInput($_POST['groupe_sanguin']);
                        $allergies = cleanInput($_POST['allergies']) ?: 'Aucune';
                        $maladies_chroniques = cleanInput($_POST['maladies_chroniques']) ?: 'Aucune';
                        
                        error_log("Données du patient : date_naissance=$date_naissance, sexe=$sexe, groupe_sanguin=$groupe_sanguin");
                        
                        if (empty($date_naissance) || empty($sexe) || empty($groupe_sanguin)) {
                            throw new Exception('La date de naissance, le sexe et le groupe sanguin sont obligatoires pour un patient.');
                        }
                        
                        $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, telephone, date_naissance, adresse, allergies, maladies_chroniques, groupe_sanguin, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')";
                        $params = [$nom, $prenom, $email, $hashed_password, $telephone, $date_naissance, $adresse, $allergies, $maladies_chroniques, $groupe_sanguin];
                        
                        error_log("SQL pour patient : " . $sql);
                        error_log("Nombre de paramètres : " . count($params));
                        error_log("Paramètres : " . print_r($params, true));
                    } else {
                        error_log("Traitement d'une nouvelle secrétaire");
                        $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, telephone) VALUES (?, ?, ?, ?, ?)";
                        $params = [$nom, $prenom, $email, $hashed_password, $telephone];
                        
                        error_log("SQL pour secrétaire : " . $sql);
                        error_log("Nombre de paramètres : " . count($params));
                        error_log("Paramètres : " . print_r($params, true));
                    }

                    error_log("Requête SQL finale : $sql");
                    error_log("Paramètres finaux : " . print_r($params, true));

                    try {
                        $stmt = $pdo->prepare($sql);
                        $result = $stmt->execute($params);

                        if (!$result) {
                            error_log("Erreur SQL : " . print_r($stmt->errorInfo(), true));
                            throw new Exception('Erreur lors de l\'insertion dans la base de données : ' . implode(', ', $stmt->errorInfo()));
                        }

                        error_log("Insertion réussie");
                    } catch (PDOException $e) {
                        error_log("Erreur PDO : " . $e->getMessage());
                        throw new Exception('Erreur lors de l\'insertion dans la base de données : ' . $e->getMessage());
                    }
                    
                    // Stocker le mot de passe dans l'URL pour l'afficher
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?password_reset=1&new_password=' . urlencode($new_password));
                    exit();
                } catch (Exception $e) {
                    error_log("Erreur lors de l'ajout d'utilisateur : " . $e->getMessage());
                    $_SESSION['error_message'] = "Erreur lors de la création du compte : " . $e->getMessage();
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
    }
    header('Location: gestion_comptes.php');
    exit();
}

require_once '../includes/header.php';

// Récupérer tous les utilisateurs
$users = [];

// Médecins
$stmt = $pdo->query("SELECT id_medecin as id, nom, prenom, email, statut, date_inscription, 'medecin' as type,
    telephone, adresse, specialite, antenne 
    FROM medecins ORDER BY nom, prenom");
$users = array_merge($users, $stmt->fetchAll());

// Secrétaires
// $stmt = $pdo->query("SELECT id_secretaire as id, nom, prenom, email, statut, date_inscription, 'secretaire' as type,
//     telephone 
//     FROM secretaire ORDER BY nom, prenom");
// $users = array_merge($users, $stmt->fetchAll());
?>

<!-- Ajouter la référence au fichier CSS spécifique pour l'admin -->
<link rel="stylesheet" href="/santeplus/assets/css/admin.css">

<div id="gestion-comptes-container" class="container-fluid">
    <div class="row">
        <div class="col-12">          
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users-cog me-2"></i>Gestion des Comptes
                    </h5>
                    <div class="d-flex">
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-user-plus me-2"></i>Nouveau Compte
                    </button>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-file-import me-2"></i>Importer
                        </button>
                        <button type="button" class="btn btn-primary" id="exportButton">
                            <i class="fas fa-file-export me-2"></i>Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
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

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Médecins</p>
                                                <h5 class="font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['type'] === 'medecin')); ?></h5>
                                                <p class="mb-0">
                                                    <span class="text-success text-sm font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['type'] === 'medecin' && $u['statut'] === 'actif')); ?></span> actifs
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                                <i class="fas fa-user-md text-lg opacity-10"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Secrétaires</p>
                                                <h5 class="font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['type'] === 'secretaire')); ?></h5>
                                                <p class="mb-0">
                                                    <span class="text-success text-sm font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['type'] === 'secretaire' && $u['statut'] === 'actif')); ?></span> actifs
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                                                <i class="fas fa-user-tie text-lg opacity-10"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="numbers">
                                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Comptes Inactifs</p>
                                                <h5 class="font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['statut'] === 'inactif')); ?></h5>
                                                <p class="mb-0">
                                                    <span class="text-danger text-sm font-weight-bolder"><?php echo count(array_filter($users, fn($u) => $u['statut'] === 'inactif')); ?></span> à réactiver
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                                <i class="fas fa-user-slash text-lg opacity-10"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-items-center mb-0" id="usersTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Utilisateur</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Antenne</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Statut</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date d'inscription</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-dot me-2 bg-<?php 
                                                echo match($user['type']) {
                                                    'patient' => 'primary',
                                                    'medecin' => 'success',
                                                    'secretaire' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>"></span>
                                            <span class="text-xs font-weight-bold"><?php echo ucfirst($user['type']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($user['type'] === 'medecin'): ?>
                                                <span class="text-xs font-weight-bold"><?php echo htmlspecialchars($user['antenne'] ?? 'Non assigné'); ?></span>
                                            <?php else: ?>
                                                <span class="text-xs text-secondary">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-<?php echo $user['statut'] === 'actif' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($user['statut']); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold"><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>" data-user-id="<?php echo $user['id']; ?>" data-user-type="<?php echo $user['type']; ?>" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['type']; ?>')" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo $user['type']; ?>')" title="Réinitialiser mot de passe">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm <?php echo $user['statut'] === 'actif' ? 'btn-danger' : 'btn-success'; ?>" onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['type']; ?>')" title="<?php echo $user['statut'] === 'actif' ? 'Désactiver' : 'Activer'; ?>">
                                                    <i class="fas <?php echo $user['statut'] === 'actif' ? 'fa-ban' : 'fa-check'; ?>"></i>
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

<?php require_once '../includes/footer.php'; ?>

<!-- Modal Ajout Utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Ajouter un nouveau compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="addUserForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="user_type" class="form-label">Type de compte</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="medecin">Médecin</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="2" required></textarea>
                    </div>

                    <!-- Champs spécifiques aux médecins -->
                    <div id="medecin_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="specialite" class="form-label">Spécialité</label>
                            <input type="text" class="form-control" id="specialite" name="specialite">
                        </div>
                        <div class="mb-3">
                            <label for="antenne" class="form-label">Antenne</label>
                            <select class="form-select" id="antenne" name="antenne" required>
                                <option value="">Sélectionnez une antenne</option>
                                <option value="ABENGOUROU">ABENGOUROU</option>
                                <option value="ABOISSO">ABOISSO</option>
                                <option value="ADJAME">ADJAME</option>
                                <option value="ADZOPE">ADZOPE</option>
                                <option value="ATTOBAN">ATTOBAN</option>
                                <option value="BOUAKE 1">BOUAKE 1</option>
                                <option value="BOUAKE2">BOUAKE2</option>
                                <option value="COCODY">COCODY</option>
                                <option value="DALOA">DALOA</option>
                                <option value="GAGNOA">GAGNOA</option>
                                <option value="KORHOGO">KORHOGO</option>
                                <option value="MAN">MAN</option>
                                <option value="MARCORY">MARCORY</option>
                                <option value="SAN-PEDRO">SAN-PEDRO</option>
                                <option value="SMF">SMF</option>
                                <option value="TREICHVILLE">TREICHVILLE</option>
                                <option value="YAMOUSSOUKRO">YAMOUSSOUKRO</option>
                                <option value="YOPOUGON">YOPOUGON</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'importation -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Importer des utilisateurs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="import_users.php" method="POST" enctype="multipart/form-data" id="importForm" onsubmit="return validateImportForm(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_user_type" class="form-label">Type d'utilisateurs à importer *</label>
                        <select class="form-select" id="import_user_type" name="user_type" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="medecin">Médecins</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">Fichier Excel (.xlsx) *</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading">Format attendu :</h6>
                        <div id="format_medecin" style="display: none;">
                            <p class="mb-0">Pour les médecins :</p>
                            <code>nom, prenom, email, telephone, adresse, specialite, antenne</code>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Antennes valides : ABENGOUROU, ABOISSO, ADJAME, ADZOPE, ATTOBAN, BOUAKE 1, BOUAKE2, COCODY, DALOA, GAGNOA, KORHOGO, MAN, MARCORY, SAN-PEDRO, SMF, TREICHVILLE, YAMOUSSOUKRO, YOPOUGON
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Importer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser DataTables
    const table = $('#usersTable').DataTable({
        language: {
            url: '/santeplus/assets/js/datatables/i18n/fr_fr.json'
        },
        dom: '<"d-none"B>frtip',
        buttons: [
            {
                extend: 'copy',
                text: 'Copier',
                className: 'dropdown-item',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'csv',
                text: 'CSV',
                className: 'dropdown-item',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'excel',
                text: 'Excel',
                className: 'dropdown-item',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'dropdown-item',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'print',
                text: 'Imprimer',
                className: 'dropdown-item',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            }
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        responsive: true,
        columnDefs: [
            {
                targets: -1,
                orderable: false,
                searchable: false
            }
        ]
    });

    // Gestionnaire d'événements pour le bouton d'exportation
    document.getElementById('exportButton').addEventListener('click', function() {
        // Créer et afficher le menu déroulant
        const dropdownMenu = document.createElement('div');
        dropdownMenu.className = 'dropdown-menu show';
        dropdownMenu.style.position = 'absolute';
        dropdownMenu.style.transform = 'translate3d(0px, 40px, 0px)';
        dropdownMenu.style.top = '0px';
        dropdownMenu.style.left = '0px';
        dropdownMenu.style.willChange = 'transform';

        // Ajouter les options d'exportation
        const options = [
            { text: 'Copier', action: 'copy' },
            { text: 'CSV', action: 'csv' },
            { text: 'Excel', action: 'excel' },
            { text: 'PDF', action: 'pdf' },
            { text: 'Imprimer', action: 'print' }
        ];

        options.forEach(option => {
            const link = document.createElement('a');
            link.className = 'dropdown-item';
            link.href = '#';
            link.textContent = option.text;
            link.addEventListener('click', function(e) {
                e.preventDefault();
                table.button('.buttons-' + option.action).trigger();
                dropdownMenu.remove();
            });
            dropdownMenu.appendChild(link);
        });

        // Positionner et afficher le menu
        const buttonRect = this.getBoundingClientRect();
        dropdownMenu.style.top = (buttonRect.bottom + window.scrollY) + 'px';
        dropdownMenu.style.left = buttonRect.left + 'px';
        document.body.appendChild(dropdownMenu);

        // Fermer le menu au clic en dehors
        const closeMenu = function(e) {
            if (!dropdownMenu.contains(e.target) && e.target !== document.getElementById('exportButton')) {
                dropdownMenu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };
        
        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 0);
    });

    // Gestion des modals d'édition
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const userType = this.getAttribute('data-user-type');
            
            // Récupérer le nom de l'utilisateur de manière plus robuste
            let userName = '';
            const row = this.closest('tr');
            if (row) {
                const nameCell = row.querySelector('td:first-child');
                if (nameCell) {
                    userName = nameCell.textContent.trim();
                }
            }
            if (!userName) {
                userName = 'l\'utilisateur';
            }

            // Supprimer l'ancien modal s'il existe
            const oldModal = document.getElementById(`editUserModal${userId}`);
            if (oldModal) {
                const oldModalInstance = bootstrap.Modal.getInstance(oldModal);
                if (oldModalInstance) {
                    oldModalInstance.dispose();
                }
                oldModal.remove();
            }

            // Créer le modal
            const modalHtml = `
                <div class="modal fade" id="editUserModal${userId}" tabindex="-1" aria-labelledby="editUserModalLabel${userId}" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editUserModalLabel${userId}">Modifier les informations de ${userName}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="" id="editUserForm${userId}">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="user_id" value="${userId}">
                                    <input type="hidden" name="user_type" value="${userType}">
                                    
                                    <div class="mb-3">
                                        <label for="nom${userId}" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom${userId}" name="nom" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="prenom${userId}" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom${userId}" name="prenom" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email${userId}" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email${userId}" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telephone${userId}" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone${userId}" name="telephone" required>
                                    </div>
                                    
                                    ${userType === 'medecin' ? `
                                    <div class="mb-3">
                                        <label for="specialite${userId}" class="form-label">Spécialité</label>
                                        <input type="text" class="form-control" id="specialite${userId}" name="specialite" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="antenne${userId}" class="form-label">Antenne</label>
                                        <select class="form-select" id="antenne${userId}" name="antenne" required>
                                            <option value="">Sélectionnez une antenne</option>
                                            <option value="ABENGOUROU">ABENGOUROU</option>
                                            <option value="ABOISSO">ABOISSO</option>
                                            <option value="ADJAME">ADJAME</option>
                                            <option value="ADZOPE">ADZOPE</option>
                                            <option value="ATTOBAN">ATTOBAN</option>
                                            <option value="BOUAKE 1">BOUAKE 1</option>
                                            <option value="BOUAKE2">BOUAKE2</option>
                                            <option value="COCODY">COCODY</option>
                                            <option value="DALOA">DALOA</option>
                                            <option value="GAGNOA">GAGNOA</option>
                                            <option value="KORHOGO">KORHOGO</option>
                                            <option value="MAN">MAN</option>
                                            <option value="MARCORY">MARCORY</option>
                                            <option value="SAN-PEDRO">SAN-PEDRO</option>
                                            <option value="SMF">SMF</option>
                                            <option value="TREICHVILLE">TREICHVILLE</option>
                                            <option value="YAMOUSSOUKRO">YAMOUSSOUKRO</option>
                                            <option value="YOPOUGON">YOPOUGON</option>
                                        </select>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="mb-3">
                                        <label for="adresse${userId}" class="form-label">Adresse</label>
                                        <textarea class="form-control" id="adresse${userId}" name="adresse"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;

            // Ajouter le nouveau modal au DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Récupérer les données de l'utilisateur
            fetch(`/santeplus/admin/get_user_data.php?user_id=${userId}&user_type=${userType}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        // Remplir le formulaire avec les données
                        Object.keys(data.data).forEach(key => {
                            const input = document.getElementById(`${key}${userId}`);
                            if (input) {
                                if (input.type === 'select-one') {
                                    input.value = data.data[key];
                                } else {
                                    input.value = data.data[key] || '';
                                }
                            }
                        });

                        // Initialiser et afficher le modal avec les options correctes
                        const modalElement = document.getElementById(`editUserModal${userId}`);
                        if (modalElement) {
                            const modal = new bootstrap.Modal(modalElement, {
                                backdrop: true,
                                keyboard: true,
                                focus: true
                            });
                            modal.show();
                        }
                    } else {
                        throw new Error(data.message || 'Erreur lors du chargement des données');
                    }
                })
                .catch(error => {
                    console.error('Erreur détaillée:', error);
                    Swal.fire({
                        title: 'Erreur',
                        text: error.message || 'Impossible de charger les données de l\'utilisateur',
                        icon: 'error'
                    });
                });
        });
    });

    // Fonction de validation du formulaire d'importation
    function validateImportForm(event) {
        event.preventDefault();
        
        const userType = document.getElementById('import_user_type').value;
        const fileInput = document.getElementById('file');
        
        if (!userType) {
            Swal.fire({
                title: 'Erreur',
                text: 'Veuillez sélectionner un type d\'utilisateur',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            Swal.fire({
                title: 'Erreur',
                text: 'Veuillez sélectionner un fichier Excel',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Si tout est valide, soumettre le formulaire
        event.target.submit();
        return true;
    }

    // Gestion de l'affichage des formats
    const importUserType = document.getElementById('import_user_type');
    const formatMedecin = document.getElementById('format_medecin');

    if (importUserType) {
        importUserType.addEventListener('change', function() {
            // Cacher tous les formats
            formatMedecin.style.display = 'none';
        });
    }

    // Réinitialiser le formulaire quand le modal est fermé
    const importModal = document.getElementById('importModal');
    if (importModal) {
        importModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('importForm');
            if (form) {
                form.reset();
                formatMedecin.style.display = 'none';
            }
        });
    }

    // Gestion du modal d'ajout d'utilisateur
    const userTypeSelect = document.getElementById('user_type');
    const medecinFields = document.getElementById('medecin_fields');
    const adresseField = document.getElementById('adresse')?.closest('.mb-3');
    const addUserForm = document.getElementById('addUserForm');

    // Fonction pour gérer les champs requis
    function updateRequiredFields(userType) {
        if (!addUserForm) return;
        
        // Réinitialiser tous les champs requis
        const allFields = addUserForm.querySelectorAll('[required]');
        allFields.forEach(field => {
            field.required = false;
        });

        // Champs de base toujours requis
        ['nom', 'prenom', 'email', 'telephone'].forEach(field => {
            const input = document.getElementById(field);
            if (input) input.required = true;
        });

        // Champs spécifiques selon le type d'utilisateur
        if (userType === 'medecin') {
            const specialite = document.getElementById('specialite');
            if (specialite) specialite.required = true;
        }

        // Adresse requise sauf pour les secrétaires
        if (userType !== 'secretaire') {
            const adresse = document.getElementById('adresse');
            if (adresse) adresse.required = true;
        }
    }

    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            const userType = this.value;
            if (medecinFields) medecinFields.style.display = userType === 'medecin' ? 'block' : 'none';
            if (adresseField) adresseField.style.display = userType === 'secretaire' ? 'none' : 'block';
            updateRequiredFields(userType);
        });
    }

    // Gestion de la soumission du formulaire
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation du formulaire
            const userType = userTypeSelect?.value;
            if (!userType) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Veuillez sélectionner un type d\'utilisateur',
                    icon: 'error'
                });
                return;
            }

            // Validation des champs obligatoires
            const requiredFields = ['nom', 'prenom', 'email', 'telephone'];
            if (userType !== 'secretaire') {
                requiredFields.push('adresse');
            }
            if (userType === 'medecin') {
                requiredFields.push('specialite');
            }

            let missingFields = [];
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input || !input.value.trim()) {
                    missingFields.push(field);
                }
            });

            if (missingFields.length > 0) {
                Swal.fire({
                    title: 'Erreur',
                    text: 'Veuillez remplir tous les champs obligatoires',
                    icon: 'error'
                });
                return;
            }

            // Si tout est valide, soumettre le formulaire
            this.submit();
        });
    }

    // Gestion des actions (suppression, réinitialisation de mot de passe, etc.)
    window.toggleStatus = function(userId, userType) {
        if (confirm('Êtes-vous sûr de vouloir changer le statut de ce compte ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="user_type" value="${userType}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };

    window.resetPassword = function(userId, userType) {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe de ce compte ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="user_type" value="${userType}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };

    window.deleteUser = function(userId, userType) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ? Cette action est irréversible.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="user_type" value="${userType}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };

    // Gestion du nouveau mot de passe
    const urlParams = new URLSearchParams(window.location.search);
    const newPassword = urlParams.get('new_password');
    
    if (newPassword) {
        Swal.fire({
            title: 'Mot de passe réinitialisé',
            html: `
                <div class="text-center">
                    <p class="mb-3">Le nouveau mot de passe provisoire est :</p>
                    <div class="alert alert-warning">
                        <strong>${newPassword}</strong>
                    </div>
                    <p class="text-muted">L'utilisateur devra changer ce mot de passe à sa prochaine connexion.</p>
                </div>
            `,
            icon: 'success',
            confirmButtonText: 'Copier et fermer',
            confirmButtonColor: '#28a745',
            showCloseButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                navigator.clipboard.writeText(newPassword).then(() => {
                    Swal.fire({
                        title: 'Copié !',
                        text: 'Le mot de passe a été copié dans le presse-papiers',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
            }
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
});
</script> 

<!-- Ajouter avant la fermeture de la balise body -->
<script src="/santeplus/assets/js/admin.js"></script> 