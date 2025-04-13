<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les données requises
if (!isset($_POST['action']) || !isset($_POST['id_urgence'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$action = cleanInput($_POST['action']);
$urgence_id = (int)$_POST['id_urgence'];

try {
    // Vérifier que l'urgence existe
    $stmt = $pdo->prepare("SELECT * FROM urgences WHERE id_urgence = ?");
    $stmt->execute([$urgence_id]);
    $urgence = $stmt->fetch();

    if (!$urgence) {
        throw new Exception('Urgence non trouvée');
    }

    // Traiter l'action selon le type
    switch ($action) {
        case 'accepter':
            // Vérifier que l'urgence est en attente
            if ($urgence['statut'] !== 'en_attente') {
                throw new Exception('Cette urgence ne peut pas être acceptée');
            }

            // Mettre à jour le statut de l'urgence
            $stmt = $pdo->prepare("
                UPDATE urgences 
                SET statut = 'en_cours', id_medecin = ?, date_modification = NOW()
                WHERE id_urgence = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $urgence_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'urgence_acceptee', ?, ?, NOW())
            ");
            $stmt->execute([
                $urgence['id_patient'],
                'Un médecin a accepté votre demande d\'urgence.',
                "/santeplus/patient/urgence-details.php?id=$urgence_id"
            ]);

            $message = "L'urgence a été acceptée avec succès";
            break;

        case 'terminer':
            // Vérifier que l'urgence est en cours
            if ($urgence['statut'] !== 'en_cours') {
                throw new Exception('Cette urgence ne peut pas être terminée');
            }

            // Vérifier que le médecin est assigné à cette urgence
            if ($urgence['id_medecin'] !== $_SESSION['user_id']) {
                throw new Exception('Vous n\'êtes pas assigné à cette urgence');
            }

            // Mettre à jour le statut de l'urgence
            $stmt = $pdo->prepare("
                UPDATE urgences 
                SET statut = 'termine', date_modification = NOW()
                WHERE id_urgence = ?
            ");
            $stmt->execute([$urgence_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'urgence_terminee', ?, ?, NOW())
            ");
            $stmt->execute([
                $urgence['id_patient'],
                'Votre demande d\'urgence a été terminée.',
                "/santeplus/patient/urgence-details.php?id=$urgence_id"
            ]);

            $message = "L'urgence a été terminée avec succès";
            break;

        case 'annuler':
            // Vérifier que l'urgence est en cours
            if ($urgence['statut'] !== 'en_cours') {
                throw new Exception('Cette urgence ne peut pas être annulée');
            }

            // Vérifier que le médecin est assigné à cette urgence
            if ($urgence['id_medecin'] !== $_SESSION['user_id']) {
                throw new Exception('Vous n\'êtes pas assigné à cette urgence');
            }

            // Mettre à jour le statut de l'urgence
            $stmt = $pdo->prepare("
                UPDATE urgences 
                SET statut = 'annule', date_modification = NOW()
                WHERE id_urgence = ?
            ");
            $stmt->execute([$urgence_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'urgence_annulee', ?, ?, NOW())
            ");
            $stmt->execute([
                $urgence['id_patient'],
                'Votre demande d\'urgence a été annulée.',
                "/santeplus/patient/urgence-details.php?id=$urgence_id"
            ]);

            $message = "L'urgence a été annulée avec succès";
            break;

        case 'message':
            // Vérifier que l'urgence n'est pas terminée ou annulée
            if (in_array($urgence['statut'], ['termine', 'annule'])) {
                throw new Exception('Impossible d\'envoyer un message à une urgence terminée ou annulée');
            }

            // Vérifier que le message n'est pas vide
            if (empty($_POST['message'])) {
                throw new Exception('Le message ne peut pas être vide');
            }

            // Insérer le message
            $stmt = $pdo->prepare("
                INSERT INTO messages_urgence (id_urgence, id_expediteur, message, date_creation)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$urgence_id, $_SESSION['user_id'], cleanInput($_POST['message'])]);

            $message = "Le message a été envoyé avec succès";
            break;

        default:
            throw new Exception('Action non reconnue');
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 