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
$id_document = cleanInput($_POST['id_document']);
$motif_suppression = cleanInput($_POST['motif_suppression']);

try {
    // Vérifier que le document existe et appartient au médecin
    $stmt = $pdo->prepare("
        SELECT d.*, p.id_patient, p.nom as patient_nom, p.prenom as patient_prenom, u.email as patient_email
        FROM documents_medicaux d
        JOIN patients p ON d.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE d.id_document = ? AND d.id_medecin = ?
    ");
    $stmt->execute([$id_document, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        throw new Exception("Document non trouvé ou accès non autorisé");
    }
    
    // Vérifier que le motif de suppression est fourni
    if (empty($motif_suppression)) {
        throw new Exception("Le motif de la suppression est obligatoire");
    }
    
    // Supprimer le fichier physique
    $chemin_fichier = '../uploads/documents/' . $document['chemin_fichier'];
    if (file_exists($chemin_fichier)) {
        if (!unlink($chemin_fichier)) {
            throw new Exception("Erreur lors de la suppression du fichier");
        }
    }
    
    // Supprimer le document de la base de données
    $stmt = $pdo->prepare("DELETE FROM documents_medicaux WHERE id_document = ?");
    $stmt->execute([$id_document]);
    
    // Créer une notification pour le patient
    $stmt = $pdo->prepare("
        INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
        VALUES (?, 'document_medical_supprime', ?, ?, NOW())
    ");
    $stmt->execute([
        $document['id_patient'],
        "Un document médical a été supprimé de votre dossier. Motif : " . $motif_suppression,
        '/santeplus/patient/documents-medicaux.php'
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 