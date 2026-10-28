<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.activer');

$currentUser = getCurrentUser();
if ($currentUser === null) {
    denyAccess();
}

$demandes = [];
$errorMessage = '';
$successMessage = (string) ($_SESSION['activation_success'] ?? '');
unset($_SESSION['activation_success']);

try {
    $connection = getDatabaseConnection();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $demandeId = filter_input(INPUT_POST, 'id_demande', FILTER_VALIDATE_INT);
        if (!is_int($demandeId) || $demandeId < 1) {
            $errorMessage = 'La demande sélectionnée est invalide';
        } else {
            try {
                activerDemande(
                    $connection,
                    $demandeId,
                    $currentUser['id_utilisateur']
                );

                $_SESSION['activation_success'] = 'Demande activée (110) et transmise au dépanneur';

                header('Location: recues.php');
                exit;

            } catch (InvalidArgumentException $exception) {
                $errorMessage = $exception->getMessage();
            } catch (RuntimeException $exception) {
                $errorMessage = 'La demande n’a pas pu être activée';
            }
        }
    }

    $demandes = getDemandesEnAttente($connection);
}
catch (RuntimeException $exception) {
    $errorMessage = 'Les demandes ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Demandes reçues | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Demandes reçues</h1>
    <p class="sous-titre">Demandes à l’état reçu (100) en attente d’examen par l’agent d’écart</p>

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

    <?php if ($demandes === []): ?>
        <p>Aucune demande reçue en attente d’activation</p>

    <?php else: ?>
        <div class="tableau-defilant">
        <table>
            <thead>
                <tr>
                    <th>N° de dossier</th>
                    <th>Client</th>
                    <th>Problème</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $demande): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars((string) $demande['reference'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['nom_client'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['type_demande'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['date_demande'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= renderStatutBadge('EN_ATTENTE') ?>
                        </td>
                        <td>
                            <form action="recues.php" method="post">
                                <input
                                    type="hidden"
                                    name="id_demande"
                                    value="<?= (int) $demande['id_demande'] ?>"
                                >
                                <button type="submit">Activer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>