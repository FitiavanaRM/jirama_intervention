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
    <?php if ($successMessage !== ''): ?>
        <p role="status">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="ajouter.php" method="post">
        <div>
            <label for="nom_equipe">Nom de l’équipe</label>
            <input
                id="nom_equipe"
                name="nom_equipe"
                type="text"
                maxlength="150"
                value="<?= htmlspecialchars($formData['nom_equipe'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div>
            <label for="type_equipe">Type d’équipe (optionnel)</label>
            <input
                id="type_equipe"
                name="type_equipe"
                type="text"
                maxlength="100"
                value="<?= htmlspecialchars($formData['type_equipe'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div>
            <label for="description">Description (optionnel)</label>
            <textarea
                id="description"
                name="description"
            ><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
</main>