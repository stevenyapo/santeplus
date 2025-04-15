<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur - SantePlus</title>
    <link rel="stylesheet" href="../assets/css/error.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-server"></i>
            </div>
            <h1>500</h1>
            <h2>Erreur serveur</h2>
            <p>Une erreur inattendue s'est produite. Notre équipe technique a été notifiée.</p>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour en arrière
                </a>
            </div>
        </div>
    </div>
</body>
</html> 