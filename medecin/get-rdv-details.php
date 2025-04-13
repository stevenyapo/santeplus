<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID du rendez-vous est fourni
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID du rendez-vous manquant']);
    exit;
}

$rdv_id = (int)$_GET['id'];

try {
    // Récupérer les détails du rendez-vous
    $stmt = $pdo->prepare("
        SELECT r.*, 
               p.nom as patient_nom, 
               p.prenom as patient_prenom,
               p.telephone as patient_telephone,
               p.date_naissance as patient_date_naissance,
               u.email as patient_email
        FROM rendez_vous r
        JOIN patients p ON r.id_patient = p.id_patient
        JOIN utilisateurs u ON p.id_utilisateur = u.id_utilisateur
        WHERE r.id_rdv = ? AND r.id_medecin = ?
    ");
    $stmt->execute([$rdv_id, $_SESSION['user_id']]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        throw new Exception('Rendez-vous non trouvé');
    }

    // Formater la réponse
    $response = [
        'rdv' => [
            'id' => $rdv['id_rdv'],
            'date' => date('d/m/Y H:i', strtotime($rdv['date_rdv'])),
            'statut' => $rdv['statut'],
            'motif' => $rdv['motif'],
            'notes' => $rdv['notes'],
            'patient' => [
                'nom' => $rdv['patient_nom'],
                'prenom' => $rdv['patient_prenom'],
                'telephone' => $rdv['patient_telephone'],
                'email' => $rdv['patient_email'],
                'date_naissance' => date('d/m/Y', strtotime($rdv['patient_date_naissance']))
            ]
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 