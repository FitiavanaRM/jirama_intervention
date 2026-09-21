<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';
require_once __DIR__ . '/../includes/interventions.php';

requirePermission('intervention.terminer');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$demandeId = filter_input(
    INPUT_GET,
    'id_demande',
    FILTER_VALIDATE_INT
);
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
<main>
