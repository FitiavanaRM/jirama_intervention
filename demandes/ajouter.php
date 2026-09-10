<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.creer');

$currentUser = getCurrentUser();

if ($currentUser === null) {
    denyAccess();
}

$fields = [
    'nom_client',
    'telephone_client',
    'adresse_client',
    'type_demande',
    'description_probleme',
];
$formData = array_fill_keys($fields, '');
$errorMessage = '';
$successMessage = (string) ($_SESSION['demande_success'] ?? '');
unset($_SESSION['demande_success']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        $formData[$field] = is_string($value) ? $value : '';
    }

    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $expectsJson = str_contains($acceptHeader, 'application/json');

    try {
        $demande = createDemande(
            getDatabaseConnection(),
            $formData,
            $currentUser['id_utilisateur']
        );

        if ($expectsJson) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(201);
            echo json_encode([
                'message' => 'Demande créée avec succès.',
                'id_demande' => $demande['id_demande'],
                'reference' => $demande['reference'],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['demande_success'] = "Demande {$demande['reference']} créée avec succès.";
        header('Location: ajouter.php');
        exit;
    } catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();

        if ($expectsJson) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(422);
            echo json_encode(['message' => $errorMessage], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (RuntimeException $exception) {
        $errorMessage = 'La demande n’a pas pu être créée.';

        if ($expectsJson) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(500);
            echo json_encode(['message' => $errorMessage], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$pageTitle = 'Nouvelle demande | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main>
    <h1>Enregistrer une demande</h1>

    <?php if ($successMessage !== ''): ?>
        <p role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="ajouter.php" method="post">
        <div>
            <label for="nom_client">Nom du client</label>
            <input id="nom_client" name="nom_client" type="text" maxlength="150" value="<?= htmlspecialchars($formData['nom_client'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label for="telephone_client">Téléphone du client</label>
            <input id="telephone_client" name="telephone_client" type="tel" maxlength="20" value="<?= htmlspecialchars($formData['telephone_client'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label for="adresse_client">Adresse du client</label>
            <textarea id="adresse_client" name="adresse_client" required><?= htmlspecialchars($formData['adresse_client'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <label for="type_demande">Type de demande</label>
            <input id="type_demande" name="type_demande" type="text" maxlength="100" value="<?= htmlspecialchars($formData['type_demande'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label for="description_probleme">Description du problème</label>
            <textarea id="description_probleme" name="description_probleme" required><?= htmlspecialchars($formData['description_probleme'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <button type="submit">Enregistrer la demande</button>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>