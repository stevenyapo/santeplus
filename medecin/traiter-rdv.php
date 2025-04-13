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
if (!isset($_POST['action']) || !isset($_POST['id_rdv'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$action = cleanInput($_POST['action']);
$rdv_id = (int)$_POST['id_rdv'];

try {
    // Vérifier que le rendez-vous existe
    $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id_rdv = ? AND id_medecin = ?");
    $stmt->execute([$rdv_id, $_SESSION['user_id']]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        throw new Exception('Rendez-vous non trouvé');
    }

    // Traiter l'action selon le type
    switch ($action) {
        case 'confirmer':
            // Vérifier que le rendez-vous est en attente
            if ($rdv['statut'] !== 'en_attente') {
                throw new Exception('Ce rendez-vous ne peut pas être confirmé');
            }

            // Mettre à jour le statut du rendez-vous
            $stmt = $pdo->prepare("
                UPDATE rendez_vous 
                SET statut = 'confirme', date_modification = NOW()
                WHERE id_rdv = ?
            ");
            $stmt->execute([$rdv_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'rdv_confirme', ?, ?, NOW())
            ");
            $stmt->execute([
                $rdv['id_patient'],
                'Votre rendez-vous a été confirmé.',
                "/santeplus/patient/rendez-vous-details.php?id=$rdv_id"
            ]);

            $message = "Le rendez-vous a été confirmé avec succès";
            break;

        case 'terminer':
            // Vérifier que le rendez-vous est confirmé
            if ($rdv['statut'] !== 'confirme') {
                throw new Exception('Ce rendez-vous ne peut pas être terminé');
            }

            // Mettre à jour le statut du rendez-vous
            $stmt = $pdo->prepare("
                UPDATE rendez_vous 
                SET statut = 'termine', date_modification = NOW()
                WHERE id_rdv = ?
            ");
            $stmt->execute([$rdv_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'rdv_termine', ?, ?, NOW())
            ");
            $stmt->execute([
                $rdv['id_patient'],
                'Votre rendez-vous a été marqué comme terminé.',
                "/santeplus/patient/rendez-vous-details.php?id=$rdv_id"
            ]);

            $message = "Le rendez-vous a été terminé avec succès";
            break;

        case 'annuler':
            // Vérifier que le rendez-vous n'est pas terminé
            if ($rdv['statut'] === 'termine') {
                throw new Exception('Ce rendez-vous ne peut pas être annulé');
            }

            // Mettre à jour le statut du rendez-vous
            $stmt = $pdo->prepare("
                UPDATE rendez_vous 
                SET statut = 'annule', date_modification = NOW()
                WHERE id_rdv = ?
            ");
            $stmt->execute([$rdv_id]);

            // Créer une notification pour le patient
            $stmt = $pdo->prepare("
                INSERT INTO notifications (id_destinataire, type, contenu, lien, date_creation)
                VALUES (?, 'rdv_annule', ?, ?, NOW())
            ");
            $stmt->execute([
                $rdv['id_patient'],
                'Votre rendez-vous a été annulé.',
                "/santeplus/patient/rendez-vous-details.php?id=$rdv_id"
            ]);

            $message = "Le rendez-vous a été annulé avec succès";
            break;

        case 'notes':
            // Vérifier que le rendez-vous n'est pas annulé
            if ($rdv['statut'] === 'annule') {
                throw new Exception('Impossible d\'ajouter des notes à un rendez-vous annulé');
            }

            // Vérifier que les notes ne sont pas vides
            if (empty($_POST['notes'])) {
                throw new Exception('Les notes ne peuvent pas être vides');
            }

            // Mettre à jour les notes
            $stmt = $pdo->prepare("
                UPDATE rendez_vous 
                SET notes = ?, date_modification = NOW()
                WHERE id_rdv = ?
            ");
            $stmt->execute([cleanInput($_POST['notes']), $rdv_id]);

            $message = "Les notes ont été mises à jour avec succès";
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