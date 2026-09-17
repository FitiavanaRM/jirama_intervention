<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/affectations.php';
require_once __DIR__ . '/../includes/techniciens.php';

requirePermission('technicien.superviser');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$formData = [
    'nom' => '',
    'prenom' => '',
    'telephone' => '',
    'fonction' => '',
    'id_equipe' => '',
    'id_utilisateur' => '',
];

$errorMessage = '';
$successMessage = (string) ($_SESSION['technicien_success'] ?? '');
unset($_SESSION['technicien_success']);

$equipes = [];
$utilisateursDisponibles = [];
$connection = getDatabaseConnection();

try {
    $connection = getDatabaseConnection();
    $equipes = getEquipes($connection);

    $utilisateursDisponibles = getUtilisateursTechnicienDisponibles($connection);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
    }

    try {
        $validatedData = validateTechnicienData($_POST);
        insertTechnicien($connection, $validatedData);
        $_SESSION['technicien_success'] = 'Technicien créé avec succès';
        header('Location: ajouter.php');
        exit;
    }
    catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } 
    catch (RuntimeException $exception) {
        $errorMessage = 'Le technicien n’a pas pu être créé';
    }
}
catch (RuntimeException $exception) {
    $errorMessage = 'Les données ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Nouveau technicien | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';