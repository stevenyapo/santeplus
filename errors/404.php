<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - SantePlus</title>
    <link rel="stylesheet" href="../assets/css/error.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1>404</h1>
            <h2>Page non trouvée</h2>
            <p>Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>
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