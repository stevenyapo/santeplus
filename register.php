<?php
require_once 'includes/header.php';

// Rediriger si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    header('Location: /santeplus/index.php');
    exit();
}

// Traiter le formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = cleanInput($_POST['nom']);
    $prenom = cleanInput($_POST['prenom']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = cleanInput($_POST['telephone']);
    $date_naissance = cleanInput($_POST['date_naissance']);
    $adresse = cleanInput($_POST['adresse']);
    
    $transaction_started = false;
    
    try {
        // Vérifier si l'email existe déjà dans toutes les tables
        $tables = ['patients', 'medecins', 'secretaire', 'administrateurs'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cet email est déjà utilisé.");
            }
        }
        
        // Vérifier si les mots de passe correspondent
        if ($password !== $confirm_password) {
            throw new Exception("Les mots de passe ne correspondent pas.");
        }
        
        // Vérifier la force du mot de passe
        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères.");
        }
        
        // Hasher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Démarrer la transaction
        $pdo->beginTransaction();
        $transaction_started = true;
        
        // Insérer dans la table patients
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                nom, prenom, email, mot_de_passe, telephone, date_naissance, adresse, date_inscription
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $nom, $prenom, $email, $hashed_password, $telephone, $date_naissance, $adresse
        ]);
        $id_patient = $pdo->lastInsertId();
        
        // Valider la transaction
        $pdo->commit();
        $transaction_started = false;
        
        // Créer la session
        $_SESSION['user_id'] = $id_patient;
        $_SESSION['role'] = 'patient';
        $_SESSION['nom'] = $nom;
        $_SESSION['prenom'] = $prenom;
        $_SESSION['success'] = "Votre compte a été créé avec succès.";
        
        // Rediriger vers le tableau de bord patient
        header('Location: /santeplus/patient/dashboard.php');
        exit();
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur seulement si elle a été démarrée
        if ($transaction_started) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">
                                    Le mot de passe doit contenir au moins 8 caractères.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" required>
                        </div>

                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3" required></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>S'inscrire
                            </button>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>Déjà un compte ? Se connecter
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du formulaire
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Validation de la date de naissance
document.getElementById('date_naissance').addEventListener('change', function() {
    const dateNaissance = new Date(this.value);
    const aujourdhui = new Date();
    const age = aujourdhui.getFullYear() - dateNaissance.getFullYear();
    
    if (age < 0) {
        alert('La date de naissance ne peut pas être dans le futur.');
        this.value = '';
        return;
    }
    
    if (age > 120) {
        alert('Veuillez entrer une date de naissance valide.');
        this.value = '';
        return;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 