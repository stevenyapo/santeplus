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

// Inclure le header
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>Mon Profil
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <div class="profile-picture mb-3">
                                <i class="fas fa-user-circle fa-6x text-primary"></i>
                            </div>
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-camera me-2"></i>Changer la photo
                            </button>
                        </div>
                        <div class="col-md-8">
                            <h5 class="mb-3">Informations personnelles</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            <?php if ($_SESSION['role'] === 'patient'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Date de naissance</label>
                                    <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($user['date_naissance'])); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Groupe sanguin</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['groupe_sanguin']); ?>" readonly>
                                </div>
                            <?php elseif ($_SESSION['role'] === 'medecin'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Spécialité</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['specialite']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Numéro de licence</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['numero_licence']); ?>" readonly>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-primary" onclick="window.location.href='/santeplus/settings.php'">
                            <i class="fas fa-cog me-2"></i>Paramètres
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