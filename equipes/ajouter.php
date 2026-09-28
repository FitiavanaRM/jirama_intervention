<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/equipes.php';

requireRole(['Administrateur']);

$formData = [
    'nom_equipe' => '',
    'type_equipe' => '',
    'description' => '',
];

$errorMessage = '';
$successMessage = (string) ($_SESSION['equipe_success'] ?? '');
unset($_SESSION['equipe_success']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($formData as $field => $defaultValue) {
        $value = $_POST[$field] ?? '';
        $formData[$field] = is_string($value) ? $value : '';
    }

    try {
        $validatedData = validateEquipeData($_POST);
        insertEquipe(getDatabaseConnection(), $validatedData);
        $_SESSION['equipe_success'] = 'Équipe créée avec succès';
        header('Location: ajouter.php');
        exit;
    } 
    catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } 
    catch (RuntimeException $exception) {
        $errorMessage = 'L’équipe n’a pas pu être créée';
    }
}

$pageTitle = 'Nouvelle équipe | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Ajouter une équipe</h1>
</main>