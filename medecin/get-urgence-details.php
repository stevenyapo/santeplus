<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID de l'urgence est fourni
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$urgence_id = (int)$_GET['id'];

try {
    // Récupérer les détails de l'urgence
    $stmt = $pdo->prepare("
        SELECT u.*, p.nom as patient_nom, p.prenom as patient_prenom, p.telephone, p.email
        FROM urgences u
        JOIN patients p ON u.id_patient = p.id_utilisateur
        WHERE u.id_urgence = ?
    ");
    $stmt->execute([$urgence_id]);
    $urgence = $stmt->fetch();

    if (!$urgence) {
        throw new Exception('Urgence non trouvée');
    }

    // Récupérer les messages de l'urgence
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom as expediteur_nom, u.prenom as expediteur_prenom
        FROM messages_urgence m
        JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur
        WHERE m.id_urgence = ?
        ORDER BY m.date_creation ASC
    ");
    $stmt->execute([$urgence_id]);
    $messages = $stmt->fetchAll();

    // Formater les données pour la réponse
    $response = [
        'urgence' => [
            'id' => $urgence['id_urgence'],
            'type' => $urgence['type'],
            'niveau' => $urgence['niveau'],
            'statut' => $urgence['statut'],
            'description' => $urgence['description'],
            'date_creation' => date('d/m/Y H:i', strtotime($urgence['date_creation'])),
            'patient' => [
                'nom' => $urgence['patient_nom'],
                'prenom' => $urgence['patient_prenom'],
                'telephone' => $urgence['telephone'],
                'email' => $urgence['email']
            ]
        ],
        'messages' => array_map(function($message) {
            return [
                'id' => $message['id_message'],
                'message' => $message['message'],
                'expediteur' => $message['expediteur_prenom'] . ' ' . $message['expediteur_nom'],
                'date' => date('H:i', strtotime($message['date_creation'])),
                'is_medecin' => $message['id_expediteur'] === $_SESSION['user_id']
            ];
        }, $messages)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 