<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/utilisateurs.php';

requireRole(['Administrateur']);

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'email' => '',
    'id_role' => '',
];

$errrorMessage = '';
$successMessage = (string) ($_SESSION['utilisateur_success'] ?? '');
unset($_SESSION['utilisateur_success']);
$roles = [];

try {
    $connection = getDatabaseConnection();
    $roles = getRoles($connection);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
        
        try {
            $validatedData = validateUtilisateurData($_POST, true);
            insertUtilisateur($connection, $ValidatedData);

            $_SESSION['utilisateur_success'] = 'Utilisateur crée avec succès';
            header('Location : ajouter.php');
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'L utilisateur n a pas pu être créer';
        }
    }
}
catch (RuntimeException $exception) {
    $erreurMessage = 'Les données ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Nouvel utilisateur | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
