<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur - SantePlus</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-code {
            font-size: 120px;
            color: #e74c3c;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            color: #333;
            margin: 20px 0;
        }
        .back-home {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .back-home:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>
        <p class="error-message">Une erreur interne du serveur s'est produite.</p>
        <a href="/santeplus/" class="back-home">Retour à l'accueil</a>
    </div>
</body>
</html> 