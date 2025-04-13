<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit();
}

// Vérifier si l'ID de la demande est fourni
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de la demande manquant.";
    header('Location: /santeplus/medecin/demandes-documents.php');
    exit();
}

$id_demande = cleanInput($_GET['id']);

try {
    // Vérifier que la demande existe et appartient au médecin
    $stmt = $pdo->prepare("
        SELECT d.*, p.id_patient, p.nom as patient_nom, p.prenom as patient_prenom, u.email as patient_email
        FROM demandes_documents d
        JOIN patients p ON d.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE d.id_demande = ? AND d.id_medecin = ? AND d.statut = 'en_cours'
    ");
    $stmt->execute([$id_demande, $_SESSION['user_id']]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        throw new Exception("Demande non trouvée ou accès non autorisé.");
    }
    
    // Traiter le formulaire de soumission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Veuillez sélectionner un fichier à télécharger.");
        }
        
        $fichier = $_FILES['document'];
        
        // Vérifier le type de fichier
        $types_autorises = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        if (!in_array($fichier['type'], $types_autorises)) {
            throw new Exception("Type de fichier non autorisé. Formats acceptés : PDF, DOC, DOCX, JPG, PNG");
        }
        
        // Vérifier la taille du fichier (max 10MB)
        if ($fichier['size'] > 10 * 1024 * 1024) {
            throw new Exception("Le fichier est trop volumineux. Taille maximum : 10MB");
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
        $nouveau_nom = uniqid() . '_' . time() . '.' . $extension;
        $chemin_destination = '../uploads/documents/' . $nouveau_nom;
        
        // Déplacer le fichier
        if (!move_uploaded_file($fichier['tmp_name'], $chemin_destination)) {
            throw new Exception("Erreur lors du téléchargement du fichier.");
        }
        
        // Insérer le document dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO documents_medicaux (id_patient, id_medecin, type, titre, description, chemin_fichier, date_creation)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $demande['id_patient'],
            $_SESSION['user_id'],
            $demande['type'],
            $demande['titre'],
            $demande['description'],
            $nouveau_nom
        ]);
        
        // Mettre à jour le statut de la demande
        $stmt = $pdo->prepare("
            UPDATE demandes_documents 
            SET statut = 'termine', date_modification = NOW()
            WHERE id_demande = ?
        ");
        $stmt->execute([$id_demande]);
        
        // Créer une notification pour le patient
        $stmt = $pdo->prepare("
            INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
            VALUES (?, 'document_medical_ajoute', ?, ?, NOW())
        ");
        $stmt->execute([
            $demande['id_patient'],
            "Un nouveau document médical a été ajouté à votre dossier : " . $demande['titre'],
            '/santeplus/patient/documents-medicaux.php'
        ]);
        
        $_SESSION['success'] = "Le document a été créé avec succès.";
        header('Location: /santeplus/medecin/demandes-documents.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /santeplus/medecin/demandes-documents.php');
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Créer un document médical</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="card-title">Informations de la demande</h5>
                    <p><strong>Patient :</strong> <?php echo htmlspecialchars($demande['patient_prenom'] . ' ' . $demande['patient_nom']); ?></p>
                    <p><strong>Type :</strong> <?php echo ucfirst($demande['type']); ?></p>
                    <p><strong>Titre :</strong> <?php echo htmlspecialchars($demande['titre']); ?></p>
                    <p><strong>Description :</strong> <?php echo htmlspecialchars($demande['description']); ?></p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="document" class="form-label">Document</label>
                    <input type="file" class="form-control" id="document" name="document" required>
                    <div class="form-text">
                        Formats acceptés : PDF, DOC, DOCX, JPG, PNG. Taille maximum : 10MB
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="demandes-documents.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Créer le document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validation du type de fichier
document.querySelector('form').addEventListener('submit', function(e) {
    const fichier = document.getElementById('document').files[0];
    if (!fichier) return;
    
    const typesAutorises = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];
    
    if (!typesAutorises.includes(fichier.type)) {
        e.preventDefault();
        alert('Type de fichier non autorisé. Formats acceptés : PDF, DOC, DOCX, JPG, PNG');
    }
    
    if (fichier.size > 10 * 1024 * 1024) {
        e.preventDefault();
        alert('Le fichier est trop volumineux. Taille maximum : 10MB');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 