<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /santeplus/login.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages-contact.php');
    exit;
}

// Récupérer et nettoyer les données
$message_id = cleanInput($_POST['message_id']);
$reponse = cleanInput($_POST['reponse']);

try {
    // Vérifier que le message existe
    $stmt = $pdo->prepare("
        SELECT * FROM messages_contact 
        WHERE id_message = ?
    ");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message non trouvé');
    }

    // Envoyer l'email de réponse
    $to = $message['email'];
    $subject = "Réponse à votre message - SantéPlus";
    $headers = "From: contact@santeplus.com\r\n";
    $headers .= "Reply-To: contact@santeplus.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $email_content = "
        <html>
        <body>
            <h2>Réponse à votre message</h2>
            <p>Bonjour {$message['nom']},</p>
            <p>Nous vous remercions pour votre message concernant : {$message['sujet']}</p>
            <p>Voici notre réponse :</p>
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                {$reponse}
            </div>
            <p>Cordialement,<br>L'équipe SantéPlus</p>
        </body>
        </html>
    ";

    if (!mail($to, $subject, $email_content, $headers)) {
        throw new Exception('Erreur lors de l\'envoi de l\'email');
    }

    // Mettre à jour le statut du message
    $stmt = $pdo->prepare("
        UPDATE messages_contact 
        SET statut = 'repondu' 
        WHERE id_message = ?
    ");
    $stmt->execute([$message_id]);

    $_SESSION['success'] = "Votre réponse a été envoyée avec succès.";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: messages-contact.php');
exit; 