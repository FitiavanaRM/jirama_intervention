<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/interventions.php';

requirePermission('intervention.terminer');

$currentUser = getCurrentUser();
// verification si l'utilisateur est connecté
if ($currentUser === null) {
    denyAccess();
}

// récupératio ny id dmd
$demandeId = filter_input(
    INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);
// récupération ny id avy any amin formulaire
if ($demandeId === false && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $demandeId = filter_input(
        INPUT_POST,
        'id_demande',
        FILTER_VALIDATE_INT
    );
}

if (!is_int($demandeId) || $demandeId < 1) {
    http_response_code(404);
    exit('Demande introuvable');
}

$formData = [
    'probleme_constate' => '',
    'travail_effectue' => '',
    'resultat' => '',
    'observation' => '',
];

$errorMessage = '';

// gestion d'erreur
try {
    $connection = getDatabaseConnection();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($formData as $field => $defaultValue) {
            $value = $_POST[$field] ?? '';
            $formData[$field] = is_string($value) ? $value : '';
        }
        try {
            $validatedData = validateInterventionFinData($_POST);
            $connection->beginTransaction();

            try {
                terminerIntervention($connection, $demandeId, $validatedData);
                updateDemandeStatut($connection, $demandeId, 'RESOLUE');
                insertHistorique(
                    $connection,
                    $demandeId,
                    $currentUser['id_utilisateur'],
                    'EN_COURS',
                    'RESOLUE'
                );
                $connection->commit();
            } catch (Throwable $exception) {
                $connection->rollBack();
                throw $exception;
            }

            $_SESSION['intervention_success'] = 'Intervention terminée avec succès';
            header('Location: liste.php');
            exit;
        }
        catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }
        catch (RuntimeException $exception) {
            $errorMessage = 'L’intervention n’a pas pu être terminée';
        }
    }
}
catch (RuntimeException $exception) {
    $errorMessage = 'Une erreur est survenue, veuillez réessayer!';
}

$pageTitle = 'Terminer l intervention | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Terminer l’intervention</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form action="terminer.php" method="post">
        <input type="hidden" name="id_demande" value="<?= (int) $demandeId ?>">
        <!Champ probleme constanté>
        <div>
            <label for="probleme_constate">Problème constaté</label>
            <textarea
                id="probleme_constate"
                name="probleme_constate"
                required
            ><?= htmlspecialchars($formData['probleme_constate'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!Champ travail effectué>
        <div>
            <label for="travail_effectue">Travail effectué</label>
            <textarea
                id="travail_effectue"
                name="travail_effectue"
                required
            ><?= htmlspecialchars($formData['travail_effectue'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!Champ resultat>
        <div>
            <label for="resultat">Résultat</label>
            <textarea
                id="resultat"
                name="resultat"
                required
            ><?= htmlspecialchars($formData['resultat'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!observation>
        <div>
            <label for="observation">Observation (optionnel)</label>
            <textarea
                id="observation"
                name="observation"
            ><?= htmlspecialchars($formData['observation'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <button type="submit">Terminer l’intervention</button>
    </form>
<main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>