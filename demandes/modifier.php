<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.modifier');

$demandeId = filter_input(
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);

if (!is_int($demandeId) || $demandeId < 1) {
    http_response_code(404);
    exit('Demande introuvable');
}

$errorMessage = '';
$successMessage = (string) ($_SESSION['demande_modif_success'] ?? '');
unset($_SESSION['demande_modif_success']);

try {
    $connection = getDatabaseConnection();
    $demande = getDemandeById($connection, $demandeId);
    if ($demande === null) {
        http_response_code(404);
        exit('Demande introuvable.');
    }

    $formData = [
        'nom_client' => (string) $demande['nom_client'],
        'telephone_client' => (string) $demande['telephone_client'],
        'adresse_client' => (string) $demande['adresse_client'],
        'type_demande' => (string) $demande['type_demande'],
        'description_probleme' => (string) $demande['description_probleme'],
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }

        try {
            $validatedData = validateDemandeData($_POST);
            updateDemande($connection, $demandeId, $validatedData);
            $_SESSION['demande_modif_success'] = 'Demande modifiée avec succès';
            header('Location: modifier.php?id_demande=' . $demandeId);
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'La demande n’a pas pu être modifiée';
        }
    }
}
catch (RuntimeException $exception) {
    http_response_code(500);
    exit('La demande ne peut pas être chargée pour le moment');
}

$pageTitle = 'Modifier la demande | JIRAMA Intervention';

define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Modifier la demande <?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?></h1>
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

    <?php if ($demande['statut'] === 'EN_ATTENTE'): ?>
        <form action="modifier.php" method="post">
            <input type="hidden" name="id_demande" value="<?= (int) $demandeId ?>">
            <div>
                <label for="nom_client">Nom du client</label>
                <input
                    id="nom_client"
                    name="nom_client"
                    type="text"
                    maxlength="150"
                    value="<?= htmlspecialchars($formData['nom_client'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="telephone_client">Téléphone du client</label>
                <input
                    id="telephone_client"
                    name="telephone_client"
                    type="tel"
                    maxlength="20"
                    value="<?= htmlspecialchars($formData['telephone_client'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="adresse_client">Adresse du client</label>
                <textarea
                    id="adresse_client"
                    name="adresse_client"
                    required
                ><?= htmlspecialchars($formData['adresse_client'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div>
                <label for="type_demande">Type de demande</label>
                <input
                    id="type_demande"
                    name="type_demande"
                    type="text"
                    maxlength="100"
                    value="<?= htmlspecialchars($formData['type_demande'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div>
                <label for="description_probleme">Description du problème</label>
                <textarea
                    id="description_probleme"
                    name="description_probleme"
                    required
                ><?= htmlspecialchars($formData['description_probleme'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <button type="submit">Enregistrer les modifications</button>
        </form>
    <?php endif; ?>
    <p>
        <a href="details.php?id_demande=<?= (int) $demandeId ?>">Retour au détail de la demande</a>
    </p>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>