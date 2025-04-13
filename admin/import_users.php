<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Logs de base
error_log("=== Début de l'importation ===");
error_log("POST : " . print_r($_POST, true));
error_log("FILES : " . print_r($_FILES, true));
error_log("SESSION : " . print_r($_SESSION, true));

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    error_log("Session non initialisée - user_id ou role manquant");
    $_SESSION['error_message'] = "Veuillez vous connecter";
    header('Location: /santeplus/login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    error_log("Rôle incorrect - Rôle actuel: " . $_SESSION['role']);
    $_SESSION['error_message'] = "Accès non autorisé - Veuillez vous connecter en tant qu'administrateur";
    header('Location: /santeplus/login.php');
    exit();
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Méthode non autorisée");
    $_SESSION['error_message'] = "Méthode non autorisée";
    header('Location: gestion_comptes.php');
    exit();
}

// Vérifier le type d'utilisateur
if (!isset($_POST['user_type']) || empty($_POST['user_type'])) {
    error_log("Type d'utilisateur manquant");
    $_SESSION['error_message'] = "Veuillez sélectionner un type d'utilisateur";
    header('Location: gestion_comptes.php');
    exit();
}

$user_type = cleanInput($_POST['user_type']);
if (!in_array($user_type, ['patient', 'medecin', 'secretaire'])) {
    error_log("Type d'utilisateur invalide : " . $user_type);
    $_SESSION['error_message'] = "Type d'utilisateur invalide";
    header('Location: gestion_comptes.php');
    exit();
}

// Vérifier le fichier
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    error_log("Erreur d'upload : " . $_FILES['file']['error']);
    $_SESSION['error_message'] = "Erreur lors de l'upload du fichier";
    header('Location: gestion_comptes.php');
    exit();
}

try {
    // Charger le fichier Excel
    $inputFileName = $_FILES['file']['tmp_name'];
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Supprimer l'en-tête
    array_shift($rows);
    
    error_log("Nombre de lignes : " . count($rows));
    
    // Déterminer la table
    $table = match($user_type) {
        'patient' => 'patients',
        'medecin' => 'medecins',
        'secretaire' => 'secretaire',
        default => throw new Exception('Type invalide')
    };
    
    $success = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        try {
            // Générer un mot de passe
            $password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Préparer la requête selon le type
            if ($user_type === 'patient') {
                $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, telephone, adresse, date_naissance, sexe, groupe_sanguin, statut) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')";
                $params = [$row[0], $row[1], $row[2], $hashed_password, $row[3], $row[4], $row[5], $row[6], $row[7]];
            } else if ($user_type === 'medecin') {
                // Valider l'antenne
                $antennes_valides = ['ABENGOUROU', 'ABOISSO', 'ADJAME', 'ADZOPE', 'ATTOBAN', 'BOUAKE 1', 'BOUAKE2', 'COCODY', 'DALOA', 'GAGNOA', 'KORHOGO', 'MAN', 'MARCORY', 'SAN-PEDRO', 'SMF', 'TREICHVILLE', 'YAMOUSSOUKRO', 'YOPOUGON'];
                if (!isset($row[6]) || !in_array(strtoupper($row[6]), $antennes_valides)) {
                    throw new Exception("Antenne invalide. Les valeurs possibles sont : " . implode(", ", $antennes_valides));
                }
                $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, telephone, adresse, specialite, antenne, statut) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'actif')";
                $params = [$row[0], $row[1], $row[2], $hashed_password, $row[3], $row[4], $row[5], strtoupper($row[6])];
            } else {
                $sql = "INSERT INTO $table (nom, prenom, email, mot_de_passe, telephone, statut) 
                        VALUES (?, ?, ?, ?, ?, 'actif')";
                $params = [$row[0], $row[1], $row[2], $hashed_password, $row[3]];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success++;
            
        } catch (Exception $e) {
            $errors[] = "Ligne " . ($index + 1) . " : " . $e->getMessage();
            error_log("Erreur ligne " . ($index + 1) . " : " . $e->getMessage());
        }
    }
    
    $_SESSION['success_message'] = "$success utilisateur(s) importé(s) avec succès";
    if (!empty($errors)) {
        $_SESSION['error_message'] = "Erreurs : " . implode("<br>", $errors);
    }
    
} catch (Exception $e) {
    error_log("Erreur générale : " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
}

header('Location: gestion_comptes.php');
exit();
?> 