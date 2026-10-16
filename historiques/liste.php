<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('historique.consulter');

$historiques = [];
$errorMessage = '';

try {
    $historiques = getHistoriques(getDatabaseConnection());
}
catch (RuntimeException $exception) {
    $errorMessage ='L’historique ne peut pas être chargé pour le moment';
}

$pageTitle = 'Historique | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>
<main>
    <h1>Historique des demandes</h1>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php elseif ($historiques === []): ?>
        <p>Aucun historique n’est enregistré.</p>
    <?php else: ?>
        <div class="tableau-defilant">
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Ancien statut</th>
                        <th>Nouveau statut</th>
                        <th>Date</th>
                        <th>Effectué par</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($historiques as $historique): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(
                                    (string) $historique['reference'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $historique['nom_client'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?php if ($historique['ancien_statut'] !== null): ?>
                                    <?= renderStatutBadge((string) $historique['ancien_statut']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= renderStatutBadge((string) $historique['nouveau_statut']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $historique['date_action'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $historique['utilisateur_prenom'] .
                                    ' ' .
                                    (string) $historique['utilisateur_nom'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>