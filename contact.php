<?php
require_once 'includes/header.php';

// Traiter le formulaire de contact
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = cleanInput($_POST['nom']);
    $email = cleanInput($_POST['email']);
    $sujet = cleanInput($_POST['sujet']);
    $message = cleanInput($_POST['message']);
    
    try {
        // Vérifier que tous les champs sont remplis
        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            throw new Exception("Veuillez remplir tous les champs.");
        }
        
        // Vérifier le format de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Veuillez entrer une adresse email valide.");
        }
        
        // Insérer le message dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO messages_contact (nom, email, sujet, message, date_creation)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nom, $email, $sujet, $message]);
        
        // Envoyer un email de confirmation
        $to = $email;
        $subject = "Confirmation de votre message - SantéPlus";
        $headers = "From: contact@santeplus.com\r\n";
        $headers .= "Reply-To: contact@santeplus.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $email_content = "
            <html>
            <body>
                <h2>Confirmation de votre message</h2>
                <p>Bonjour {$nom},</p>
                <p>Nous avons bien reçu votre message concernant : {$sujet}</p>
                <p>Notre équipe vous répondra dans les plus brefs délais.</p>
                <p>Cordialement,<br>L'équipe SantéPlus</p>
            </body>
            </html>
        ";
        
        mail($to, $subject, $email_content, $headers);
        
        $_SESSION['success'] = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 text-body">
                        <i class="fas fa-envelope me-2"></i>Contactez-nous
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="sujet" class="form-label">Sujet</label>
                            <select class="form-select" id="sujet" name="sujet" required>
                                <option value="">Sélectionnez un sujet</option>
                                <option value="rendez-vous">Rendez-vous</option>
                                <option value="urgences">Urgences</option>
                                <option value="documents">Documents médicaux</option>
                                <option value="teleconsultation">Téléconsultation</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 text-body">
                        <i class="fas fa-info-circle me-2"></i>Informations de contact
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="mb-3 text-body">Adresse</h6>
                        <p class="mb-0 text-body">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            123 Rue de la Santé<br>
                            75000 Paris
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-body">Téléphone</h6>
                        <p class="mb-0">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            <a href="tel:0123456789" class="text-decoration-none text-body">01 23 45 67 89</a>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-body">Email</h6>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <a href="mailto:contact@santeplus.com" class="text-decoration-none text-body">contact@santeplus.com</a>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3 text-body">Horaires d'ouverture</h6>
                        <p class="mb-0 text-body">
                            <i class="fas fa-clock me-2 text-primary"></i>
                            Lundi - Vendredi : 8h00 - 20h00<br>
                            Samedi : 9h00 - 17h00<br>
                            Dimanche : Fermé
                        </p>
                    </div>

                    <div>
                        <h6 class="mb-3 text-body">Urgences</h6>
                        <p class="mb-0 text-body">
                            <i class="fas fa-ambulance me-2 text-danger"></i>
                            En cas d'urgence, appelez le 15 ou le 112
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0 text-body">
                        <i class="fas fa-map me-2"></i>Notre emplacement
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.142047477456!2d2.295159076456344!3d48.85837007922345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66fc4c0c5c0c5%3A0x0!2zNDjCsDUxJzMwLjEiTiAywrAxNyc0NS4wIkU!5e0!3m2!1sfr!2sfr!4v1625767890123!5m2!1sfr!2sfr"
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
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
</script>

<?php require_once 'includes/footer.php'; ?> 