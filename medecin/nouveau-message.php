<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un médecin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medecin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier si une conversation existe déjà
        $stmt = $pdo->prepare("
            SELECT id_conversation 
            FROM conversations 
            WHERE id_medecin = ? AND id_admin = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_POST['id_admin']]);
        $conversation = $stmt->fetch();

        // Si aucune conversation n'existe, en créer une nouvelle
        if (!$conversation) {
            $stmt = $pdo->prepare("
                INSERT INTO conversations (id_medecin, id_admin, dernier_message, date_dernier_message)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $_POST['id_admin'], $_POST['message']]);
            $id_conversation = $pdo->lastInsertId();
        } else {
            $id_conversation = $conversation['id_conversation'];
            
            // Mettre à jour le dernier message
            $stmt = $pdo->prepare("
                UPDATE conversations 
                SET dernier_message = ?, date_dernier_message = NOW()
                WHERE id_conversation = ?
            ");
            $stmt->execute([$_POST['message'], $id_conversation]);
        }

        // Insérer le message
        $stmt = $pdo->prepare("
            INSERT INTO messages (id_conversation, id_expediteur, message, date_envoi)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$id_conversation, $_SESSION['user_id'], $_POST['message']]);

        // Rediriger vers la conversation
        header('Location: conversation.php?id=' . $id_conversation);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue lors de l'envoi du message.";
        header('Location: messages.php');
        exit;
    }
} else {
    // Si le formulaire n'a pas été soumis, rediriger vers la page des messages
    header('Location: messages.php');
    exit;
}
?> 