<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/equipes.php';

requireRole(['Administrateur']);

$equipeId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_equipe',
    FILTER_VALIDATE_INT
);

if (!is_int($equipeId) || $equipeId < 1) {
    http_response_code(404);
    exit('Équipe introuvable.');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['equipe_success'] ?? '');
unset($_SESSION['equipe_success']);

try {
    $connection = getDatabaseConnection();

    $equipe = getEquipeById($connection, $equipeId);

    if ($equipe === null) {
        http_response_code(404);
        exit('Équipe introuvable.');
    }

    $formData = [
        'nom_equipe' => (string) $equipe['nom_equipe'],
        'type_equipe' => (string) ($equipe['type_equipe'] ?? ''),
        'description' => (string) ($equipe['description'] ?? ''),
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }

        try {
            $validatedData = validateEquipeData($_POST);
            updateEquipe($connection, $equipeId, $validatedData);
            $_SESSION['equipe_success'] = 'Équipe modifiée avec succès.';

            header('Location: modifier.php?id_equipe=' . $equipeId);
            exit;

        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'L’équipe n’a pas pu être modifiée.';
        }
    }
} catch (RuntimeException $exception) {
    http_response_code(500);
    exit('L’équipe ne peut pas être chargée pour le moment');
}

$pageTitle = 'Modifier une équipe | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Modifier l’équipe</h1>

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

    <form action="modifier.php" method="post">

        <input type="hidden" name="id_equipe" value="<?= (int) $equipeId ?>">

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
        <button type="submit">Enregistrer les modifications</button>
    </form>

    <p>
        <a href="liste.php">Retour à la liste des équipes</a>
    </p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>