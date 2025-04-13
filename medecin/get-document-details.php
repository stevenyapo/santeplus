<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID du document est fourni
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du document manquant']);
    exit();
}

$id_document = cleanInput($_GET['id']);

try {
    // Récupérer les détails du document
    $stmt = $pdo->prepare("
        SELECT d.*, p.nom as patient_nom, p.prenom as patient_prenom, 
               u.email as patient_email, m.nom as medecin_nom, m.prenom as medecin_prenom
        FROM documents_medicaux d
        JOIN patients p ON d.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        JOIN medecins m ON d.id_medecin = m.id_medecin
        WHERE d.id_document = ? AND d.id_medecin = ?
    ");
    $stmt->execute([$id_document, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        throw new Exception("Document non trouvé ou accès non autorisé");
    }
    
    // Formater les dates
    $date_creation = date('d/m/Y H:i', strtotime($document['date_creation']));
    
    // Préparer la réponse HTML
    $html = '
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Détails du document</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3">Informations du patient</h6>
                    <p><strong>Nom :</strong> ' . htmlspecialchars($document['patient_prenom'] . ' ' . $document['patient_nom']) . '</p>
                    <p><strong>Email :</strong> ' . htmlspecialchars($document['patient_email']) . '</p>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Informations du document</h6>
                    <p><strong>Type :</strong> <span class="badge bg-' . getTypeBadgeColor($document['type']) . '">' . ucfirst($document['type']) . '</span></p>
                    <p><strong>Titre :</strong> ' . htmlspecialchars($document['titre']) . '</p>
                    <p><strong>Date de création :</strong> ' . $date_creation . '</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="mb-3">Description</h6>
                    <p>' . nl2br(htmlspecialchars($document['description'])) . '</p>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="mb-3">Actions</h6>
                    <div class="d-flex gap-2">
                        <a href="/santeplus/uploads/documents/' . $document['chemin_fichier'] . '" class="btn btn-primary" target="_blank">
                            <i class="fas fa-download me-2"></i>Télécharger
                        </a>
                        <button type="button" class="btn btn-danger" onclick="supprimerDocument(' . $id_document . ')">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Fonction pour déterminer la couleur du badge selon le type de document
function getTypeBadgeColor($type) {
    switch ($type) {
        case 'prescription':
            return 'success';
        case 'resultat':
            return 'info';
        case 'certificat':
            return 'warning';
        default:
            return 'secondary';
    }
} 