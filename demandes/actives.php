<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demandes.php';

requirePermission('demande.active.consulter');

$demandes = [];
$errorMessage = '';

try {
    $demandes = getDemandesActives(getDatabaseConnection());
} catch (RuntimeException $exception) {
    $errorMessage = 'Les demandes actives ne peuvent pas être chargées pour le moment';
}

$pageTitle = 'Demandes actives | JIRAMA Intervention';
define('JIRAMA_LAYOUT', true);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main>
    <h1>Demandes actives</h1>
    <p class="sous-titre">Demandes activées par l’agent d’écart (110) et transmises au dépanneur</p>
    <?php if ($errorMessage !== ''): ?>
        <p role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif ($demandes === []): ?>
        <p>Aucune demande active pour le moment</p>

    <?php else: ?>
        <div class="tableau-defilant">
        <table>
            <thead>
                <tr>
                    <th>N° de dossier</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Adresse</th>
                    <th>Problème</th>
                    <th>Date</th>
                    <th>Intervention</th>
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
                            <?= htmlspecialchars((string) $demande['telephone_client'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['adresse_client'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($demande['repere_adresse'])): ?>
                                <br>
                                <small>
                                    Repère :
                                    <?= htmlspecialchars((string) $demande['repere_adresse'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['description_probleme'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $demande['date_demande'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?php if (($demande['code_intervention'] ?? null) !== null): ?>
                                <?= renderCodeInterventionBadge((string) $demande['code_intervention']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>