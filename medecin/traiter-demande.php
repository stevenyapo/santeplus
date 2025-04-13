<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isLoggedIn() || $_SESSION['role'] !== 'medecin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer et nettoyer les données
$id_demande = cleanInput($_POST['id_demande']);
$action = cleanInput($_POST['action']);
$commentaire = isset($_POST['commentaire']) ? cleanInput($_POST['commentaire']) : null;
$motif_refus = isset($_POST['motif_refus']) ? cleanInput($_POST['motif_refus']) : null;

try {
    // Vérifier que la demande existe et appartient au médecin
    $stmt = $pdo->prepare("
        SELECT d.*, p.id_patient, p.nom as patient_nom, p.prenom as patient_prenom, u.email as patient_email
        FROM demandes_documents d
        JOIN patients p ON d.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE d.id_demande = ? AND d.id_medecin = ?
    ");
    $stmt->execute([$id_demande, $_SESSION['user_id']]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        throw new Exception("Demande non trouvée ou accès non autorisé.");
    }
    
    // Vérifier que l'action est valide
    if (!in_array($action, ['accepter', 'refuser', 'terminer'])) {
        throw new Exception("Action non valide.");
    }
    
    // Vérifier que le statut actuel permet l'action
    switch ($action) {
        case 'accepter':
            if ($demande['statut'] !== 'en_attente') {
                throw new Exception("Cette demande ne peut pas être acceptée.");
            }
            $nouveau_statut = 'en_cours';
            $message_notification = "Votre demande de document a été acceptée. Le médecin va la traiter dans les plus brefs délais.";
            break;
            
        case 'refuser':
            if ($demande['statut'] !== 'en_attente') {
                throw new Exception("Cette demande ne peut pas être refusée.");
            }
            if (!$motif_refus) {
                throw new Exception("Le motif du refus est obligatoire.");
            }
            $nouveau_statut = 'refuse';
            $message_notification = "Votre demande de document a été refusée. Motif : " . $motif_refus;
            break;
            
        case 'terminer':
            if ($demande['statut'] !== 'en_cours') {
                throw new Exception("Cette demande ne peut pas être terminée.");
            }
            $nouveau_statut = 'termine';
            $message_notification = "Votre demande de document a été marquée comme terminée. Le document est disponible dans votre dossier.";
            break;
    }
    
    // Mettre à jour le statut de la demande
    $stmt = $pdo->prepare("
        UPDATE demandes_documents 
        SET statut = ?, commentaire = ?, date_modification = NOW()
        WHERE id_demande = ?
    ");
    $stmt->execute([$nouveau_statut, $commentaire, $id_demande]);
    
    // Créer une notification pour le patient
    $stmt = $pdo->prepare("
        INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
        VALUES (?, 'demande_document_modifiee', ?, ?, NOW())
    ");
    $stmt->execute([
        $demande['id_patient'],
        $message_notification,
        '/santeplus/patient/documents-medicaux.php'
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 